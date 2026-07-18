<?php

use App\Models\Team;
use App\Models\User;
use App\Services\TeamMergeService;
use App\Services\TeamRoleService;
use App\Support\PermissionTeam;
use Spatie\Permission\Models\Role;

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
