<?php

/**
 * GC-Stats — Admin panel routes
 *
 * Every route is gated by its own permission from App\Support\AdminPermissions
 * (one per action) rather than a shared umbrella permission — `/admin` itself
 * only requires the 'access-admin' gate (true for any role holding at least
 * one of those permissions; always true for super-admin via Gate::before).
 * Role/permission management is locked to the super-admin-only 'manage-roles'
 * gate, see AppServiceProvider.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SanctionController;
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
