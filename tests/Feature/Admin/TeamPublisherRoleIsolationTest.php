<?php

use App\Models\NewsPublisher;
use App\Models\Team;
use App\Models\User;
use App\Services\PublisherRoleService;
use App\Services\TeamMergeService;
use App\Support\PermissionTeam;
use App\Support\PublisherPermissions;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

/**
 * Team and NewsPublisher roles share the same numeric `team_id` pivot
 * column (spatie's "teams" feature repurposed for two independent scoping
 * domains) and are only disambiguated by `guard_name` ('web' for teams,
 * 'publisher' for publishers). Because ids from both tables are assigned
 * independently, a Team and a NewsPublisher can end up sharing the same
 * numeric id — this test pins that collision on purpose and asserts
 * TeamMergeService::delete()'s defensive Role cleanup (a leftover from when
 * teams could hold their own self-service roles, see its docblock) never
 * touches the like-numbered publisher's roles.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('deleting a team does not delete a like-numbered publisher\'s roles', function () {
    $team = Team::factory()->create();

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
