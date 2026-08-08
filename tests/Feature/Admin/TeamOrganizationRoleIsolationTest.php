<?php

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Services\TeamMergeService;
use App\Support\OrganizationPermissions;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

/**
 * Team and Organization roles share the same numeric `team_id` pivot column
 * (spatie's "teams" feature repurposed for two independent scoping domains)
 * and are only disambiguated by `guard_name` ('web' for teams, 'organization'
 * for organizations). Because ids from both tables are assigned
 * independently, a Team and an Organization can end up sharing the same
 * numeric id — this test pins that collision on purpose and asserts
 * TeamMergeService::delete()'s defensive Role cleanup (a leftover from when
 * teams could hold their own self-service roles, see its docblock) never
 * touches the like-numbered organization's roles.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('deleting a team does not delete a like-numbered organization\'s roles', function () {
    $team = Team::factory()->create();

    $organization = Organization::create([
        'name' => 'Colliding Organization',
        'slug' => 'colliding-organization-'.$team->id,
        'types' => ['media'],
        'socials' => [],
        'max_permissions' => OrganizationPermissions::all(),
    ]);

    // Force the id collision the production bug depends on: both rows
    // scoped through App\Support\PermissionTeam::use($id) under the same
    // numeric id, distinguished only by guard_name.
    Organization::where('id', $organization->id)->update(['id' => $team->id]);
    $organization = Organization::find($team->id);

    $owner = User::factory()->create();
    app(OrganizationRoleService::class)->assign($owner, $organization, OrganizationRoleService::ROLE_OWNER);

    $organizationRoleIds = Role::where('team_id', $organization->id)
        ->where('guard_name', OrganizationPermissions::GUARD)
        ->pluck('id');

    expect($organizationRoleIds)->not->toBeEmpty();

    app(TeamMergeService::class)->delete($team, User::factory()->create());

    expect(Role::whereIn('id', $organizationRoleIds)->count())->toBe($organizationRoleIds->count());

    PermissionTeam::use($organization->id);
    expect($owner->fresh()->hasRole(OrganizationRoleService::ROLE_OWNER, OrganizationPermissions::GUARD))->toBeTrue();
    PermissionTeam::global();
});
