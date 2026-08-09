<?php

use App\Models\ApiKey;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Support\OrganizationPermissions;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

/**
 * Covers the API keys integration into the organization dashboard:
 * Admin\ApiKeyController's index()/regenerate() are dual-context like
 * Admin\StreamChannelController, but unlike streams/news an organization
 * can only view its own keys and regenerate them — create/rename/(de)activate
 * stay strictly admin-only, and no organization-dashboard route exists for
 * those at all (see the controller's docblock and routes/organization.php).
 * The owner of an admin-created key is picked via the entity-picker toggle
 * (owner_type + owner_id_{type}) instead of a free-text username, and can be
 * either a User or an Organization.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeApiKeysOrganization(string $slug): Organization
{
    return Organization::create([
        'name' => $slug,
        'slug' => $slug,
        'types' => ['media'],
        'socials' => [],
        'max_permissions' => OrganizationPermissions::all(),
    ]);
}

function makeApiKeysOrganizationEditor(Organization $organization): User
{
    $user = User::factory()->create();
    app(OrganizationRoleService::class)->assign($user, $organization, OrganizationRoleService::ROLE_OWNER);
    PermissionTeam::global();

    return $user;
}

test('an organization member can view their dashboard api keys list', function () {
    $organization = makeApiKeysOrganization('dash-keys-a');
    $user = makeApiKeysOrganizationEditor($organization);

    $this->actingAs($user)
        ->get(route('organization-dashboard.api-keys.index', $organization))
        ->assertOk();
});

test('an organization member cannot view another organization dashboard api keys list', function () {
    $organizationA = makeApiKeysOrganization('dash-keys-b');
    $organizationB = makeApiKeysOrganization('dash-keys-c');
    $user = makeApiKeysOrganizationEditor($organizationA);

    $this->actingAs($user)
        ->get(route('organization-dashboard.api-keys.index', $organizationB))
        ->assertForbidden();
});

test('the dashboard api keys list only shows keys belonging to that organization', function () {
    $organization = makeApiKeysOrganization('dash-keys-h');
    $otherOrganization = makeApiKeysOrganization('dash-keys-h-other');
    $user = makeApiKeysOrganizationEditor($organization);

    ApiKey::create([
        'organization_id' => $organization->id,
        'client_name' => 'Mine',
        'rate_limit' => 60,
        'is_active' => true,
        'key_hash' => ApiKey::hashKey('mine-clear-key'),
    ]);

    ApiKey::create([
        'organization_id' => $otherOrganization->id,
        'client_name' => 'Not mine',
        'rate_limit' => 60,
        'is_active' => true,
        'key_hash' => ApiKey::hashKey('not-mine-clear-key'),
    ]);

    $response = $this->actingAs($user)
        ->get(route('organization-dashboard.api-keys.index', $organization))
        ->assertOk();

    $response->assertSee('Mine');
    $response->assertDontSee('Not mine');
});

test('an organization member can regenerate a key belonging to their organization', function () {
    $organization = makeApiKeysOrganization('dash-keys-e');
    $user = makeApiKeysOrganizationEditor($organization);

    $key = ApiKey::create([
        'organization_id' => $organization->id,
        'client_name' => 'Original name',
        'rate_limit' => 60,
        'is_active' => true,
        'key_hash' => ApiKey::hashKey('clear-key-value'),
    ]);

    $originalHash = $key->key_hash;

    $this->actingAs($user)
        ->patch(route('organization-dashboard.api-keys.regenerate', [$organization, $key]))
        ->assertRedirect();

    expect($key->refresh()->key_hash)->not->toBe($originalHash);
    // Regenerating never changes name/rate-limit/status/ownership.
    expect($key->client_name)->toBe('Original name');
});

test('an organization member cannot regenerate a key belonging to another organization via the dashboard url', function () {
    $organizationA = makeApiKeysOrganization('dash-keys-f');
    $organizationB = makeApiKeysOrganization('dash-keys-g');
    $userA = makeApiKeysOrganizationEditor($organizationA);

    $keyB = ApiKey::create([
        'organization_id' => $organizationB->id,
        'client_name' => 'Org B key',
        'rate_limit' => 60,
        'is_active' => true,
        'key_hash' => ApiKey::hashKey('org-b-clear-key'),
    ]);

    $this->actingAs($userA)
        ->patch(route('organization-dashboard.api-keys.regenerate', [$organizationA, $keyB]))
        ->assertNotFound();
});

test('no create, update or toggle route exists under the organization dashboard for api keys', function () {
    expect(Route::has('organization-dashboard.api-keys.store'))->toBeFalse();
    expect(Route::has('organization-dashboard.api-keys.update'))->toBeFalse();
    expect(Route::has('organization-dashboard.api-keys.toggle'))->toBeFalse();
});

test('an admin can create a key owned by an organization via the owner entity picker', function () {
    $organization = makeApiKeysOrganization('dash-keys-i');

    $admin = User::factory()->create();
    PermissionTeam::global();
    $admin->givePermissionTo(['api-keys.view', 'api-keys.manage']);

    $this->actingAs($admin)
        ->post(route('admin.api-keys.store'), [
            'client_name' => 'Org owned client',
            'rate_limit' => 60,
            'owner_type' => 'organization',
            'owner_id_organization' => $organization->id,
        ])
        ->assertRedirect();

    $key = ApiKey::where('client_name', 'Org owned client')->firstOrFail();
    expect($key->organization_id)->toBe($organization->id);
    expect($key->user_id)->toBeNull();
});

test('an admin can create a key owned by a user via the owner entity picker', function () {
    $owner = User::factory()->create();

    $admin = User::factory()->create();
    PermissionTeam::global();
    $admin->givePermissionTo(['api-keys.view', 'api-keys.manage']);

    $this->actingAs($admin)
        ->post(route('admin.api-keys.store'), [
            'client_name' => 'User owned client',
            'rate_limit' => 60,
            'owner_type' => 'user',
            'owner_id_user' => $owner->id,
        ])
        ->assertRedirect();

    $key = ApiKey::where('client_name', 'User owned client')->firstOrFail();
    expect($key->user_id)->toBe($owner->id);
    expect($key->organization_id)->toBeNull();
});

test('an admin can move a key from a user to an organization via update', function () {
    $organization = makeApiKeysOrganization('dash-keys-j');
    $originalOwner = User::factory()->create();

    $key = ApiKey::create([
        'user_id' => $originalOwner->id,
        'client_name' => 'Movable key',
        'rate_limit' => 60,
        'is_active' => true,
        'key_hash' => ApiKey::hashKey('movable-clear-key'),
    ]);

    $admin = User::factory()->create();
    PermissionTeam::global();
    $admin->givePermissionTo(['api-keys.view', 'api-keys.manage']);

    $this->actingAs($admin)
        ->patch(route('admin.api-keys.update', $key), [
            'client_name' => 'Movable key',
            'rate_limit' => 60,
            'owner_type' => 'organization',
            'owner_id_organization' => $organization->id,
        ])
        ->assertRedirect();

    $key->refresh();
    expect($key->organization_id)->toBe($organization->id);
    expect($key->user_id)->toBeNull();
});

test('the admin api keys list still works, shows every key including organization-owned ones, and admins can still toggle/regenerate them', function () {
    $organization = makeApiKeysOrganization('dash-keys-k');

    $key = ApiKey::create([
        'organization_id' => $organization->id,
        'client_name' => 'Org owned key',
        'rate_limit' => 60,
        'is_active' => true,
        'key_hash' => ApiKey::hashKey('admin-visible-clear-key'),
    ]);

    $admin = User::factory()->create();
    PermissionTeam::global();
    $admin->givePermissionTo(['api-keys.view', 'api-keys.manage']);

    $this->actingAs($admin)
        ->get(route('admin.api-keys.index'))
        ->assertOk()
        ->assertSee('Org owned key');

    $this->actingAs($admin)
        ->patch(route('admin.api-keys.toggle', $key))
        ->assertRedirect();

    expect($key->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('admin.api-keys.regenerate', $key))
        ->assertRedirect();
});
