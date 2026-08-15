<?php

use App\Jobs\ModerateForumMessage;
use App\Models\ForumThread;
use App\Models\User;
use App\Services\ForumService;
use App\Services\OpenAiModerationService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

test('posting a message dispatches moderation after the response instead of inline', function () {
    Bus::fake();

    $user = User::factory()->create();
    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Perf test', 'created_by' => $user->id]);

    app(ForumService::class)->postMessage($thread, $user, 'hello');

    // assertDispatchedAfterResponse() alone proves it wasn't also an
    // ordinary inline dispatch() — Bus::fake() records each dispatch
    // under exactly one bucket (dispatched/dispatchedAfterResponse/
    // dispatchedSync).
    Bus::assertDispatchedAfterResponse(ModerateForumMessage::class);
});

test('creating a general thread dispatches moderation after the response instead of inline', function () {
    Bus::fake();

    $user = User::factory()->create();

    app(ForumService::class)->createGeneralThread($user, 'Title', 'body');

    // assertDispatchedAfterResponse() alone proves it wasn't also an
    // ordinary inline dispatch() — Bus::fake() records each dispatch
    // under exactly one bucket (dispatched/dispatchedAfterResponse/
    // dispatchedSync).
    Bus::assertDispatchedAfterResponse(ModerateForumMessage::class);
});

test('a repeated moderation check for the same text hits OpenAI only once', function () {
    config(['services.openai.key' => 'test-key']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [['flagged' => false, 'categories' => []]],
        ], 200),
    ]);

    $service = app(OpenAiModerationService::class);

    $service->check('some repeated spam text');
    $service->check('some repeated spam text');

    Http::assertSentCount(1);
});

test('a failed OpenAI call is not cached, so it is retried next time', function () {
    config(['services.openai.key' => 'test-key']);

    Http::fake(['api.openai.com/*' => Http::response('server error', 500)]);

    $service = app(OpenAiModerationService::class);

    $first = $service->check('some text');
    $second = $service->check('some text');

    expect($first)->toBe(['flagged' => false, 'categories' => []])
        ->and($second)->toBe(['flagged' => false, 'categories' => []]);

    Http::assertSentCount(2);
});

test('different text is checked independently, not served from another text cache entry', function () {
    config(['services.openai.key' => 'test-key']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [['flagged' => false, 'categories' => []]],
        ], 200),
    ]);

    $service = app(OpenAiModerationService::class);

    $service->check('text one');
    $service->check('text two');

    Http::assertSentCount(2);
});
