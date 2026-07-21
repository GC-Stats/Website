<?php

use App\Models\Sanction;
use App\Models\User;
use App\Services\SanctionService;
use App\Support\AdminPermissions;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    PermissionTeam::global();
});

function grantGlobalPermissions(User $user, array $permissions): void
{
    PermissionTeam::global();
    $user->givePermissionTo($permissions);
}

function makeSuperAdmin(): User
{
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->assignRole('super-admin');

    return $user;
}

test('a plain user with no admin permission, no author profile and no publisher membership cannot reach the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a user with a single admin permission can only reach routes gated by that permission', function () {
    $user = User::factory()->create();
    grantGlobalPermissions($user, ['teams.view']);

    $this->actingAs($user)->get(route('admin.teams.index'))->assertOk();
    $this->actingAs($user)->get(route('admin.players.index'))->assertForbidden();
});

test('role management is forbidden to a non-super-admin even with every AdminPermissions permission', function () {
    $user = User::factory()->create();
    grantGlobalPermissions($user, AdminPermissions::all());

    $this->actingAs($user)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

test('a super-admin can reach role management', function () {
    $superAdmin = makeSuperAdmin();

    $this->actingAs($superAdmin)
        ->get(route('admin.roles.index'))
        ->assertOk();
});

test('a super-admin cannot remove their own super-admin role', function () {
    $superAdmin = makeSuperAdmin();

    PermissionTeam::global();
    $role = Role::where('name', 'super-admin')->where('team_id', 0)->firstOrFail();

    $this->actingAs($superAdmin)
        ->delete(route('admin.roles.members.destroy', [$role, $superAdmin]))
        ->assertSessionHasErrors('role');

    expect($superAdmin->fresh()->isSuperAdmin())->toBeTrue();
});

test('the super-admin role itself cannot be edited or deleted even by a super-admin', function () {
    $superAdmin = makeSuperAdmin();

    PermissionTeam::global();
    $role = Role::where('name', 'super-admin')->where('team_id', 0)->firstOrFail();

    $this->actingAs($superAdmin)
        ->put(route('admin.roles.update', $role), ['permissions' => []])
        ->assertSessionHasErrors('role');

    $this->actingAs($superAdmin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertSessionHasErrors('role');

    expect(Role::where('name', 'super-admin')->where('team_id', 0)->exists())->toBeTrue();
});

test('a role update cannot grant a permission outside the AdminPermissions catalog', function () {
    $superAdmin = makeSuperAdmin();

    $role = Role::create(['name' => 'custom-role']);

    $this->actingAs($superAdmin)
        ->put(route('admin.roles.update', $role), [
            'permissions' => ['not-a-real-permission'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

test('sanctions.revoke cannot hard-delete a sanction and sanctions.delete is required for that', function () {
    $moderator = User::factory()->create();
    grantGlobalPermissions($moderator, ['sanctions.view', 'sanctions.create', 'sanctions.revoke']);

    $target = User::factory()->create();
    $sanction = app(SanctionService::class)->issue($target, $moderator, [
        'type' => Sanction::TYPE_SUSPENSION,
        'reason' => 'test',
    ]);

    // sanctions.revoke only -> forceDestroy (hard delete) route is forbidden.
    $this->actingAs($moderator)
        ->delete(route('admin.sanctions.force-destroy', $sanction))
        ->assertForbidden();

    expect(Sanction::find($sanction->id))->not->toBeNull();

    // ...but the soft revoke route works.
    $this->actingAs($moderator)
        ->delete(route('admin.sanctions.destroy', $sanction))
        ->assertRedirect();

    expect($sanction->fresh()->revoked_at)->not->toBeNull();
});
