<?php

/**
 * GC-Stats — Team management routes
 *
 * Self-service team management: profile/logo editing (Team\ProfileController,
 * shared with the admin panel via TeamProfileService — see its docblock) and
 * per-team role management (Team\RoleController). Every action is gated by
 * its own team.* permission (see App\Support\TeamPermissions), held by that
 * team's own team_owner by default but not hardcoded to it — a site admin
 * can grant/revoke any of them per team. All routes carry the team's slug
 * (/team/{team}/{slug}/...), matching the public team pages' URL shape.
 * Registered before the public `/team/{id}/{slug?}` catch-all in web.php so
 * these aren't swallowed by it.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Team\ProfileController;
use App\Http\Controllers\Team\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'team.permission-context'])
    ->prefix('team/{team}/{slug}')
    ->group(function () {
        Route::get('/edit', [ProfileController::class, 'edit'])->name('teams.edit');
        Route::put('/edit', [ProfileController::class, 'update'])
            ->middleware('can:team.profile.edit')->name('teams.update');
        Route::post('/edit/logo', [ProfileController::class, 'updateLogo'])
            ->middleware('can:team.logo.upload')->name('teams.logo.update');

        Route::prefix('roles')->name('teams.roles.')->group(function () {
            Route::middleware(['can:team.roles.manage'])->group(function () {
                Route::get('/', [RoleController::class, 'index'])->name('index');
                Route::post('/', [RoleController::class, 'store'])->name('store');
                Route::get('/{role}', [RoleController::class, 'show'])->name('show');
                Route::put('/{role}', [RoleController::class, 'update'])->name('update');
                Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');

                Route::post('/{role}/members', [RoleController::class, 'addMember'])->name('members.store');
                Route::delete('/{role}/members/{user}', [RoleController::class, 'removeMember'])->name('members.destroy');
            });
        });
    });
