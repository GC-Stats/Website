<?php

use App\Models\Sanction;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamRoleService;
use App\Support\TeamPermissions;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a user under an active team-scoped sanction is blocked from team management routes', function () {
    $team = Team::factory()->create(['max_permissions' => TeamPermissions::all()]);
    $user = User::factory()->create();

    app(TeamRoleService::class)->assign($user, $team, TeamRoleService::ROLE_MANAGER);

    Sanction::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'type' => Sanction::TYPE_SUSPENSION,
        'reason' => 'Test sanction',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('teams.edit', [$team->id, $team->routeSlug()]))
        ->assertForbidden();
});

test('a user under an active global sanction is blocked from team management routes', function () {
    $team = Team::factory()->create(['max_permissions' => TeamPermissions::all()]);
    $user = User::factory()->create();

    app(TeamRoleService::class)->assign($user, $team, TeamRoleService::ROLE_MANAGER);

    Sanction::create([
        'user_id' => $user->id,
        'team_id' => null,
        'type' => Sanction::TYPE_BAN,
        'reason' => 'Test global sanction',
        'starts_at' => now()->subDay(),
        'ends_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('teams.edit', [$team->id, $team->routeSlug()]))
        ->assertForbidden();
});

test('a user without any active sanction can still reach team management routes', function () {
    $team = Team::factory()->create(['max_permissions' => TeamPermissions::all()]);
    $user = User::factory()->create();

    app(TeamRoleService::class)->assign($user, $team, TeamRoleService::ROLE_MANAGER);

    $this->actingAs($user)
        ->get(route('teams.edit', [$team->id, $team->routeSlug()]))
        ->assertOk();
});

test('a revoked team sanction no longer blocks access', function () {
    $team = Team::factory()->create(['max_permissions' => TeamPermissions::all()]);
    $user = User::factory()->create();

    app(TeamRoleService::class)->assign($user, $team, TeamRoleService::ROLE_MANAGER);

    Sanction::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'type' => Sanction::TYPE_SUSPENSION,
        'reason' => 'Old sanction',
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->addWeek(),
        'revoked_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('teams.edit', [$team->id, $team->routeSlug()]))
        ->assertOk();
});
