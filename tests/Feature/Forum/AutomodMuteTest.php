<?php

use App\Jobs\ModerateForumMessage;
use App\Models\ForumMessage;
use App\Models\ForumThread;
use App\Models\ModerationSuspect;
use App\Models\Sanction;
use App\Models\User;
use App\Services\OpenAiModerationService;
use App\Services\SanctionService;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    PermissionTeam::global();
});

function makeForumMessage(User $user, string $body = 'hello'): ForumMessage
{
    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Test thread', 'created_by' => $user->id]);

    return $thread->messages()->create(['user_id' => $user->id, 'body' => $body]);
}

test('a 3rd automod flag mutes the user for 6 hours', function () {
    $this->mock(OpenAiModerationService::class)
        ->shouldReceive('check')
        ->andReturn(['flagged' => true, 'categories' => ['harassment']]);

    $user = User::factory()->create();
    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Test thread', 'created_by' => $user->id]);

    ModerationSuspect::create(['subject_type' => 'forum_message', 'subject_id' => 1, 'thread_id' => $thread->id, 'user_id' => $user->id, 'matched_term' => 'x', 'body_snapshot' => 'x']);
    ModerationSuspect::create(['subject_type' => 'forum_message', 'subject_id' => 2, 'thread_id' => $thread->id, 'user_id' => $user->id, 'matched_term' => 'x', 'body_snapshot' => 'x']);

    $message = makeForumMessage($user);

    ModerateForumMessage::dispatchSync($message->id, $message->body);

    $mute = $user->fresh()->activeGlobalMuteSanction();

    expect($mute)->not->toBeNull()
        ->and($mute->type)->toBe(Sanction::TYPE_MUTE)
        ->and($mute->issued_by)->toBeNull()
        ->and($mute->ends_at->diffInHours(now()))->toBeLessThanOrEqual(6);
});

test('automod never mutes a staff account', function () {
    $this->mock(OpenAiModerationService::class)
        ->shouldReceive('check')
        ->andReturn(['flagged' => true, 'categories' => ['harassment']]);

    $staff = User::factory()->create();
    PermissionTeam::global();
    $staff->assignRole('super-admin');

    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Test thread', 'created_by' => $staff->id]);

    ModerationSuspect::create(['subject_type' => 'forum_message', 'subject_id' => 1, 'thread_id' => $thread->id, 'user_id' => $staff->id, 'matched_term' => 'x', 'body_snapshot' => 'x']);
    ModerationSuspect::create(['subject_type' => 'forum_message', 'subject_id' => 2, 'thread_id' => $thread->id, 'user_id' => $staff->id, 'matched_term' => 'x', 'body_snapshot' => 'x']);

    $message = makeForumMessage($staff);

    ModerateForumMessage::dispatchSync($message->id, $message->body);

    expect($staff->fresh()->activeGlobalMuteSanction())->toBeNull();
});

test('a muted user cannot post, and lifting the mute restores posting', function () {
    $user = User::factory()->create();
    $sanctions = app(SanctionService::class);
    $sanction = $sanctions->issueSystemMute($user, 'test mute', new DateInterval('PT6H'));

    $this->actingAs($user)
        ->post(route('forum.general.store'), ['title' => 'Hi', 'body' => 'Hello everyone'])
        ->assertForbidden();

    $sanctions->revoke($sanction, User::factory()->create());

    expect($user->fresh()->activeGlobalMuteSanction())->toBeNull();

    $this->actingAs($user)
        ->post(route('forum.general.store'), ['title' => 'Hi', 'body' => 'Hello everyone'])
        ->assertRedirect();
});

test('issueSystemMute does not stack on an already-muted user', function () {
    $user = User::factory()->create();
    $sanctions = app(SanctionService::class);

    $first = $sanctions->issueSystemMute($user, 'first', new DateInterval('PT6H'));
    $second = $sanctions->issueSystemMute($user, 'second', new DateInterval('PT6H'));

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull();
});
