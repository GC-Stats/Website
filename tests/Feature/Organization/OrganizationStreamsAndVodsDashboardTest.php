<?php

use App\Models\Matchs;
use App\Models\Organization;
use App\Models\StreamChannel;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Vod;
use App\Services\OrganizationRoleService;
use App\Support\OrganizationPermissions;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;

/**
 * Covers the streams/VOD integration into the organization dashboard:
 * Admin\StreamChannelController (full CRUD, dual-context like
 * Admin\NewsController) and Admin\MatchStreamController /
 * Admin\MatchVodController (index()/create() dual-context only — the
 * linking mutations were already organization-agnostic).
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeStreamsOrganization(string $slug): Organization
{
    return Organization::create([
        'name' => $slug,
        'slug' => $slug,
        'types' => ['media'],
        'socials' => [],
        'max_permissions' => OrganizationPermissions::all(),
    ]);
}

function makeStreamsOrganizationEditor(Organization $organization): User
{
    $user = User::factory()->create();
    app(OrganizationRoleService::class)->assign($user, $organization, OrganizationRoleService::ROLE_OWNER);
    PermissionTeam::global();

    return $user;
}

test('an organization member can view their dashboard stream channels list', function () {
    $organization = makeStreamsOrganization('dash-streams-a');
    $user = makeStreamsOrganizationEditor($organization);

    $this->actingAs($user)
        ->get(route('organization-dashboard.streams.index', $organization))
        ->assertOk();
});

test('an organization member cannot view another organization dashboard stream channels list', function () {
    $organizationA = makeStreamsOrganization('dash-streams-b');
    $organizationB = makeStreamsOrganization('dash-streams-c');
    $user = makeStreamsOrganizationEditor($organizationA);

    $this->actingAs($user)
        ->get(route('organization-dashboard.streams.index', $organizationB))
        ->assertForbidden();
});

test('an organization member can create and edit a stream channel from the dashboard, forced to their own organization', function () {
    $organization = makeStreamsOrganization('dash-streams-d');
    $otherOrganization = makeStreamsOrganization('dash-streams-d-other');
    $user = makeStreamsOrganizationEditor($organization);

    $this->actingAs($user)
        ->post(route('organization-dashboard.streams.store', $organization), [
            'organization_id' => $otherOrganization->id, // must be ignored/overridden
            'name' => 'Dashboard Channel',
            'platform' => StreamChannel::PLATFORMS[0],
            'url' => 'https://twitch.tv/dashboard-channel',
            'language_code' => 'inter',
        ])
        ->assertRedirect();

    $channel = StreamChannel::where('name', 'Dashboard Channel')->firstOrFail();
    expect($channel->organization_id)->toBe($organization->id);

    $this->actingAs($user)
        ->get(route('organization-dashboard.streams.edit', [$organization, $channel]))
        ->assertOk();

    $this->actingAs($user)
        ->put(route('organization-dashboard.streams.update', [$organization, $channel]), [
            'name' => 'Dashboard Channel Updated',
            'platform' => StreamChannel::PLATFORMS[0],
            'url' => 'https://twitch.tv/dashboard-channel',
            'language_code' => 'inter',
        ])
        ->assertRedirect();

    expect($channel->refresh()->name)->toBe('Dashboard Channel Updated');
});

test('an organization member cannot edit a stream channel belonging to another organization via the dashboard url', function () {
    $organizationA = makeStreamsOrganization('dash-streams-e');
    $organizationB = makeStreamsOrganization('dash-streams-f');
    $userA = makeStreamsOrganizationEditor($organizationA);

    $channelB = StreamChannel::create([
        'organization_id' => $organizationB->id,
        'name' => 'Org B channel',
        'platform' => StreamChannel::PLATFORMS[0],
        'url' => 'https://twitch.tv/org-b',
        'language_code' => 'inter',
        'is_active' => true,
    ]);

    $this->actingAs($userA)
        ->get(route('organization-dashboard.streams.edit', [$organizationA, $channelB]))
        ->assertNotFound();
});

test('an organization member can view the dashboard matches-with-streams list and link wizard', function () {
    $organization = makeStreamsOrganization('dash-streams-g');
    $user = makeStreamsOrganizationEditor($organization);

    $this->actingAs($user)
        ->get(route('organization-dashboard.streams.matches.index', $organization))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('organization-dashboard.streams.matches.create', $organization))
        ->assertOk();
});

test('the admin stream channels list still works and is unaffected by the dashboard scoping', function () {
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo(['streams.channels.view']);

    $this->actingAs($user)
        ->get(route('admin.streams.index'))
        ->assertOk();
});

test('an organization member can view the dashboard VOD matches list and create wizard', function () {
    $organization = makeStreamsOrganization('dash-vods-a');
    $user = makeStreamsOrganizationEditor($organization);

    $this->actingAs($user)
        ->get(route('organization-dashboard.vods.index', $organization))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('organization-dashboard.vods.create', $organization))
        ->assertOk();
});

test('an organization member cannot view another organization dashboard VOD matches list', function () {
    $organizationA = makeStreamsOrganization('dash-vods-b');
    $organizationB = makeStreamsOrganization('dash-vods-c');
    $user = makeStreamsOrganizationEditor($organizationA);

    $this->actingAs($user)
        ->get(route('organization-dashboard.vods.index', $organizationB))
        ->assertForbidden();
});

test('linking a VOD to a match still works from the fixed admin route regardless of dashboard context', function () {
    $organization = makeStreamsOrganization('dash-vods-d');
    $user = makeStreamsOrganizationEditor($organization);

    $tournament = Tournament::factory()->create();
    $match = Matchs::factory()->create(['tournament_id' => $tournament->id]);

    $this->actingAs($user)
        ->post(route('admin.matches.vods.store', [$tournament, $match]), [
            'url' => 'https://youtube.com/watch?v=abc',
            'language_code' => 'inter',
        ])
        ->assertRedirect();

    $vod = Vod::where('match_id', $match->id)->firstOrFail();
    expect($vod->organization_id)->toBe($organization->id);
});
