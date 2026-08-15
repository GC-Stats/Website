<?php

use App\Models\ForumThread;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;

function threadFor(User $user): ForumThread
{
    return ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Test thread', 'created_by' => $user->id]);
}

test('a gif token from an allowed giphy host renders as a gif segment', function () {
    $user = User::factory()->create();
    $message = threadFor($user)->messages()->create([
        'user_id' => $user->id,
        'body' => 'look at this {{gif:https://media3.giphy.com/media/abc123/giphy.gif}}',
    ]);

    $segments = $message->parseBody();

    expect($segments)->toHaveCount(2)
        ->and($segments[0]['type'])->toBe('text')
        ->and($segments[1]['type'])->toBe('gif')
        ->and($segments[1]['url'])->toBe('https://media3.giphy.com/media/abc123/giphy.gif');
});

test('a gif token from a disallowed host renders as missing', function () {
    $user = User::factory()->create();
    $message = threadFor($user)->messages()->create([
        'user_id' => $user->id,
        'body' => '{{gif:https://evil.example.com/x.gif}}',
    ]);

    $segments = $message->parseBody();

    expect($segments)->toHaveCount(1)
        ->and($segments[0]['type'])->toBe('missing');
});

test('a match player embed with a player not in the match renders as missing', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();
    $match = Matchs::factory()->create(['team_a_id' => $teamA->id, 'team_b_id' => $teamB->id]);
    $strangerPlayer = Player::factory()->create();

    $message = threadFor($user)->messages()->create([
        'user_id' => $user->id,
        'body' => "{{match:{$match->id}:player?player={$strangerPlayer->id}}}",
    ]);

    $segments = $message->parseBody();

    expect($segments)->toHaveCount(1)
        ->and($segments[0]['type'])->toBe('missing');
});

test('a match scoreboard embed resolves even with no stats recorded yet', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();
    $match = Matchs::factory()->create(['team_a_id' => $teamA->id, 'team_b_id' => $teamB->id]);

    $message = threadFor($user)->messages()->create([
        'user_id' => $user->id,
        'body' => "{{match:{$match->id}:scoreboard}}",
    ]);

    $segments = $message->parseBody();

    expect($segments)->toHaveCount(1)
        ->and($segments[0]['type'])->toBe('embed')
        ->and($segments[0]['match_data']['stats_a'])->toBe([])
        ->and($segments[0]['match_data']['stats_b'])->toBe([]);
});

test('match scoreboard and performance embeds render with real stats without throwing', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();
    $match = Matchs::factory()->create(['team_a_id' => $teamA->id, 'team_b_id' => $teamB->id]);
    $playerA = Player::factory()->create(['handle' => 'AlphaPlayer']);
    $playerB = Player::factory()->create(['handle' => 'BravoPlayer']);

    GamePlayerStat::factory()->create(['match_id' => $match->id, 'player_id' => $playerA->id, 'team_id' => $teamA->id]);
    GamePlayerStat::factory()->create(['match_id' => $match->id, 'player_id' => $playerB->id, 'team_id' => $teamB->id]);

    $thread = threadFor($user);
    $messagesByVariant = [
        'scoreboard' => $thread->messages()->create(['user_id' => $user->id, 'body' => "{{match:{$match->id}:scoreboard}}"]),
        'performance' => $thread->messages()->create(['user_id' => $user->id, 'body' => "{{match:{$match->id}:performance}}"]),
        // Economy is round-tier based, no per-player breakdown — just needs to not throw.
        'economy' => $thread->messages()->create(['user_id' => $user->id, 'body' => "{{match:{$match->id}:economy}}"]),
    ];

    foreach ($messagesByVariant as $variant => $message) {
        $segment = $message->parseBody()[0];

        expect($segment['type'])->toBe('embed');

        $html = view('components.forum.embed-card', [
            'type' => $segment['entity_type'],
            'model' => $segment['model'],
            'variant' => $segment['variant'],
            'stats' => $segment['stats'],
            'filters' => $segment['filters'],
            'matchData' => $segment['match_data'],
        ])->render();

        if (in_array($variant, ['scoreboard', 'performance'], true)) {
            expect($html)->toContain('AlphaPlayer')->and($html)->toContain('BravoPlayer');
        }
    }
});

test('the safe embed card wrapper degrades to missing instead of throwing when a card errors', function () {
    // A null $model makes embed-card.blade.php's header branch call
    // toScoreHeaderArray() on null — a guaranteed render-time error, the
    // same class of bug (undefined array key) that slipped through
    // earlier. The wrapper must swallow it and render a "missing" note
    // instead of a 500.
    $html = view('components.forum.safe-embed-card', [
        'type' => 'match',
        'model' => null,
        'variant' => 'header',
        'stats' => null,
        'filters' => null,
        'matchData' => null,
    ])->render();

    expect($html)->not->toBeEmpty()
        ->and($html)->not->toContain('Undefined');
});
