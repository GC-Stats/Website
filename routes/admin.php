<?php

/**
 * GC-Stats — Admin panel routes
 *
 * Every route is gated by its own permission from App\Support\AdminPermissions.
 * Role/permission management is locked to the super-admin-only 'manage-roles'
 * gate instead, see AppServiceProvider.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SanctionController;
use App\Http\Controllers\Admin\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(['can:reports.view'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{userReport}', [ReportController::class, 'show'])->name('reports.show');
    });
    Route::patch('/reports/{userReport}', [ReportController::class, 'resolve'])
        ->middleware('can:reports.resolve')->name('reports.resolve');

    Route::get('/activity', [ActivityLogController::class, 'index'])
        ->middleware('can:activity.view')->name('activity.index');

    Route::get('/sanctions', [SanctionController::class, 'index'])
        ->middleware('can:sanctions.view')->name('sanctions.index');
    Route::post('/sanctions', [SanctionController::class, 'store'])
        ->middleware('can:sanctions.create')->name('sanctions.store');
    Route::delete('/sanctions/{sanction}', [SanctionController::class, 'destroy'])
        ->middleware('can:sanctions.revoke')->name('sanctions.destroy');
    Route::delete('/sanctions/{sanction}/force', [SanctionController::class, 'forceDestroy'])
        ->middleware('can:sanctions.delete')->name('sanctions.force-destroy');

    Route::prefix('teams')->name('teams.')->group(function () {
        Route::middleware(['can:teams.view'])->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::get('/{team}', [TeamController::class, 'show'])->name('show');
            Route::get('/{team}/merge', [TeamController::class, 'showMerge'])
                ->middleware('can:teams.merge')->name('merge.show');
        });

        Route::middleware(['can:teams.edit'])->group(function () {
            Route::put('/{team}', [TeamController::class, 'updateProfile'])->name('update');
            Route::prefix('{team}/logo')->name('logo.')->group(function () {
                Route::post('/', [TeamController::class, 'updateLogo'])->name('update');
                Route::post('/history', [TeamController::class, 'storeLogoHistory'])->name('history.store');
                Route::put('/history/{logo}', [TeamController::class, 'updateLogoEntry'])->name('history.update');
                Route::delete('/history/{logo}', [TeamController::class, 'destroyLogoEntry'])->name('history.destroy');
            });
            Route::put('/{team}/max-permissions', [TeamController::class, 'updateMaxPermissions'])->name('max-permissions.update');
            Route::post('/{team}/owner', [TeamController::class, 'assignOwner'])->name('owner.store');
            Route::delete('/{team}/owner/{user}', [TeamController::class, 'removeOwner'])->name('owner.destroy');

            Route::prefix('{team}/roster')->name('roster.')->group(function () {
                Route::post('/', [TeamController::class, 'storeRosterMember'])->name('store');
                Route::put('/{entry}', [TeamController::class, 'updateRosterMember'])->name('update');
                Route::delete('/{entry}', [TeamController::class, 'destroyRosterMember'])->name('destroy');
            });
        });

        Route::delete('/{team}', [TeamController::class, 'destroy'])
            ->middleware('can:teams.delete')->name('destroy');
        Route::post('/{team}/merge', [TeamController::class, 'merge'])
            ->middleware('can:teams.merge')->name('merge.execute');
    });

    Route::prefix('players')->name('players.')->group(function () {
        Route::middleware(['can:players.view'])->group(function () {
            Route::get('/', [PlayerController::class, 'index'])->name('index');
            Route::get('/{player}', [PlayerController::class, 'show'])->name('show');
            Route::get('/{player}/merge', [PlayerController::class, 'showMerge'])
                ->middleware('can:players.merge')->name('merge.show');
        });

        Route::middleware(['can:players.edit'])->group(function () {
            Route::put('/{player}', [PlayerController::class, 'updateProfile'])->name('update');
            Route::delete('/{player}/val-id', [PlayerController::class, 'resetValId'])->name('val-id.destroy');
            Route::delete('/{player}/discord-id', [PlayerController::class, 'resetDiscordId'])->name('discord-id.destroy');
            Route::prefix('{player}/logo')->name('logo.')->group(function () {
                Route::post('/', [PlayerController::class, 'updateLogo'])->name('update');
                Route::post('/history', [PlayerController::class, 'storeLogoHistory'])->name('history.store');
                Route::put('/history/{logo}', [PlayerController::class, 'updateLogoEntry'])->name('history.update');
                Route::delete('/history/{logo}', [PlayerController::class, 'destroyLogoEntry'])->name('history.destroy');
            });
        });

        Route::put('/{player}/identifiers', [PlayerController::class, 'updateIdentifiers'])
            ->middleware('can:players.identifiers.manage')->name('identifiers.update');

        Route::delete('/{player}', [PlayerController::class, 'destroy'])
            ->middleware('can:players.delete')->name('destroy');
        Route::post('/{player}/merge', [PlayerController::class, 'merge'])
            ->middleware('can:players.merge')->name('merge.execute');
    });

    Route::middleware(['can:manage-roles'])->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');

        Route::post('/{role}/members', [RoleController::class, 'addMember'])->name('members.store');
        Route::delete('/{role}/members/{user}', [RoleController::class, 'removeMember'])->name('members.destroy');

        Route::put('/{role}/discord-mapping', [RoleController::class, 'updateDiscordMapping'])->name('discord-mapping.update');
        Route::delete('/{role}/discord-mapping', [RoleController::class, 'destroyDiscordMapping'])->name('discord-mapping.destroy');
    });
});
