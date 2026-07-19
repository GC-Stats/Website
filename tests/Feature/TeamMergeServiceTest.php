<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\PlayerMergeService;
use App\Services\RosterService;
use App\Services\TeamMergeService;
use App\Services\TeamRoleService;
use App\Support\PermissionTeam;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('merging roles moves the assignment from source to target and provisions target roles', function () {
    $source = Team::factory()->create();
    $target = Team::factory()->create();
    $user = User::factory()->create();

    app(TeamRoleService::class)->assign($user, $source, TeamRoleService::ROLE_MANAGER);

    $sourceRole = Role::where('team_id', $source->id)->where('name', TeamRoleService::ROLE_MANAGER)->firstOrFail();

    app(TeamMergeService::class)->merge($source, $target, [
        'roles' => ["{$sourceRole->id}:{$user->id}"],
    ], $user);

    PermissionTeam::use($source->id);
    $user->unsetRelation('roles');
    expect($user->hasRole(TeamRoleService::ROLE_MANAGER))->toBeFalse();

    PermissionTeam::use($target->id);
    $user->unsetRelation('roles');
    expect($user->hasRole(TeamRoleService::ROLE_MANAGER))->toBeTrue();
});

test('merging roles ignores a pair that is not a real assignment on source', function () {
    $source = Team::factory()->create();
    $target = Team::factory()->create();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    app(TeamRoleService::class)->assign($user, $source, TeamRoleService::ROLE_MANAGER);
    $sourceRole = Role::where('team_id', $source->id)->where('name', TeamRoleService::ROLE_MANAGER)->firstOrFail();

    // $otherUser never actually held this role — a tampered pair.
    app(TeamMergeService::class)->merge($source, $target, [
        'roles' => ["{$sourceRole->id}:{$otherUser->id}"],
    ], $user);

    PermissionTeam::use($target->id);
    $otherUser->unsetRelation('roles');
    expect($otherUser->hasRole(TeamRoleService::ROLE_MANAGER))->toBeFalse();

    PermissionTeam::use($source->id);
    $user->unsetRelation('roles');
    expect($user->hasRole(TeamRoleService::ROLE_MANAGER))->toBeTrue();
});

test('assigning or revoking a team role restores the global permission context afterward', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    app(TeamRoleService::class)->assign($user, $team, TeamRoleService::ROLE_MANAGER);
    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(PermissionTeam::GLOBAL_ID);

    app(TeamRoleService::class)->revoke($user, $team, TeamRoleService::ROLE_MANAGER);
    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(PermissionTeam::GLOBAL_ID);
});

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
