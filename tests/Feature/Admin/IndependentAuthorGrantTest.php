<?php

use App\Models\User;
use App\Support\AdminPermissions;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;

/**
 * Covers Admin\UserController::toggleNewsAuthor(): granting 'news.author'
 * directly on a user (no Role, no model_has_roles row) so an independent
 * author never shows up as site staff on the public About Us page — see
 * that method's docblock and App\Http\Controllers\Public\AboutController.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    PermissionTeam::global();
});

function makeSuperAdminUser(): User
{
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->assignRole('super-admin');

    return $user;
}

test('a non-super-admin cannot toggle independent author access even with every AdminPermissions permission', function () {
    $admin = User::factory()->create();
    PermissionTeam::global();
    $admin->givePermissionTo(AdminPermissions::all());

    $target = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.news-author.toggle', $target))
        ->assertForbidden();
});

test('a super-admin can grant independent author access without creating a global role', function () {
    $superAdmin = makeSuperAdminUser();
    $target = User::factory()->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.users.news-author.toggle', $target))
        ->assertRedirect();

    PermissionTeam::global();
    expect($target->fresh()->hasPermissionTo('news.author'))->toBeTrue();
    expect($target->fresh()->roles()->count())->toBe(0);
});

test('toggling again revokes independent author access', function () {
    $superAdmin = makeSuperAdminUser();
    $target = User::factory()->create();
    PermissionTeam::global();
    $target->givePermissionTo('news.author');

    $this->actingAs($superAdmin)
        ->put(route('admin.users.news-author.toggle', $target))
        ->assertRedirect();

    PermissionTeam::global();
    expect($target->fresh()->hasPermissionTo('news.author'))->toBeFalse();
});

test('an independent author does not appear in the About Us team listing', function () {
    $target = User::factory()->create();
    PermissionTeam::global();
    $target->givePermissionTo('news.author');

    $this->get(route('about'))
        ->assertOk()
        ->assertDontSee($target->name);
});
