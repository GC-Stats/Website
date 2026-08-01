<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\PlayerMergeService;
use App\Services\RosterService;
use App\Services\TeamMergeService;
use Illuminate\Support\Facades\Cache;

test('deleting a team flushes the cache of every player that was on its roster', function () {
    $team = Team::factory()->create();
    $player = Player::factory()->create();
    $user = User::factory()->create();

    app(RosterService::class)->addMember($team, $player->id, 'player', '2025-01-01');

    Cache::tags(["player_{$player->id}"])->put('probe', 'stale', now()->addDay());

    app(TeamMergeService::class)->delete($team, $user);

    expect(Cache::tags(["player_{$player->id}"])->get('probe'))->toBeNull();
});

test('deleting a player flushes the cache of every team it was rostered on', function () {
    $team = Team::factory()->create();
    $player = Player::factory()->create();
    $user = User::factory()->create();

    app(RosterService::class)->addMember($team, $player->id, 'player', '2025-01-01');

    Cache::tags(["team_{$team->id}"])->put('probe', 'stale', now()->addDay());

    app(PlayerMergeService::class)->delete($player, $user);

    expect(Cache::tags(["team_{$team->id}"])->get('probe'))->toBeNull();
});
