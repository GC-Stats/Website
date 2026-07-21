<?php

use App\Models\NewsPublisher;
use App\Models\Team;
use App\Models\User;
use App\Services\PublisherRoleService;
use App\Services\TeamMergeService;
use App\Services\TeamRoleService;
use App\Support\PermissionTeam;
use App\Support\PublisherPermissions;
use App\Support\TeamPermissions;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

/**
 * Team and NewsPublisher roles share the same numeric `team_id` pivot
 * column (spatie's "teams" feature repurposed for two independent
 * scoping domains) and are only disambiguated by `guard_name` ('web' for
 * teams, 'publisher' for publishers). Because ids from both tables are
 * assigned independently, a Team and a NewsPublisher can end up sharing
 * the same numeric id — these tests pin that collision on purpose and
 * assert team-scoped role operations never touch the like-numbered
 * publisher's roles.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function collidingTeamAndPublisher(): array
{
    $team = Team::factory()->create(['max_permissions' => TeamPermissions::all()]);

    $publisher = NewsPublisher::create([
        'name' => 'Colliding Publisher',
        'slug' => 'colliding-publisher-'.$team->id,
        'socials' => [],
        'max_permissions' => PublisherPermissions::all(),
    ]);

    // Force the id collision the production bug depends on: both rows
    // scoped through App\Support\PermissionTeam::use($id) under the same
    // numeric id, distinguished only by guard_name.
    NewsPublisher::where('id', $publisher->id)->update(['id' => $team->id]);
    $publisher = NewsPublisher::find($team->id);

    return [$team, $publisher];
}

test('deleting a team does not delete a like-numbered publisher\'s roles', function () {
    [$team, $publisher] = collidingTeamAndPublisher();

    $owner = User::factory()->create();
    app(PublisherRoleService::class)->assign($owner, $publisher, PublisherRoleService::ROLE_OWNER);

    $publisherRoleIds = Role::where('team_id', $publisher->id)
        ->where('guard_name', PublisherPermissions::GUARD)
        ->pluck('id');

    expect($publisherRoleIds)->not->toBeEmpty();

    app(TeamMergeService::class)->delete($team, User::factory()->create());

    expect(Role::whereIn('id', $publisherRoleIds)->count())->toBe($publisherRoleIds->count());

    PermissionTeam::use($publisher->id);
    expect($owner->fresh()->hasRole(PublisherRoleService::ROLE_OWNER, PublisherPermissions::GUARD))->toBeTrue();
    PermissionTeam::global();
});

test('updating a team\'s max permissions does not strip a like-numbered publisher\'s role permissions', function () {
    [$team, $publisher] = collidingTeamAndPublisher();

    $owner = User::factory()->create();
    app(PublisherRoleService::class)->assign($owner, $publisher, PublisherRoleService::ROLE_OWNER);

    PermissionTeam::use($publisher->id);
    $ownerRole = Role::where('name', PublisherRoleService::ROLE_OWNER)
        ->where('guard_name', PublisherPermissions::GUARD)
        ->where('team_id', $publisher->id)
        ->first();
    $permissionsBefore = $ownerRole->permissions->pluck('name')->sort()->values()->all();
    PermissionTeam::global();

    expect($permissionsBefore)->not->toBeEmpty();

    $admin = User::factory()->create();
    PermissionTeam::global();
    $admin->givePermissionTo(['teams.edit']);

    $this->actingAs($admin)
        ->put(route('admin.teams.max-permissions.update', $team), [
            'max_permissions' => ['team.profile.edit'],
        ])
        ->assertRedirect();

    PermissionTeam::use($publisher->id);
    $permissionsAfter = $ownerRole->fresh()->permissions->pluck('name')->sort()->values()->all();
    PermissionTeam::global();

    expect($permissionsAfter)->toBe($permissionsBefore);
});

test('a team\'s own roles are still provisioned when a like-numbered publisher already has roles', function () {
    [$team, $publisher] = collidingTeamAndPublisher();

    $owner = User::factory()->create();
    app(PublisherRoleService::class)->assign($owner, $publisher, PublisherRoleService::ROLE_OWNER);
    // Publisher only provisions 2 roles (owner + editor); ensure the count
    // check in TeamRoleService can't be satisfied by publisher-guard rows.
    app(PublisherRoleService::class)->ensureRolesExist($publisher);

    app(TeamRoleService::class)->ensureRolesExist($team);

    $teamRoleNames = Role::where('team_id', $team->id)
        ->where('guard_name', 'web')
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($teamRoleNames)->toBe([
        TeamRoleService::ROLE_EDITOR,
        TeamRoleService::ROLE_MANAGER,
        TeamRoleService::ROLE_OWNER,
    ]);
});
