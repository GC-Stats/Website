<?php

/**
 * GC-Stats — Developers panel routes
 *
 * Every route is gated by its own permission from App\Support\DeveloperPermissions.
 * Role/permission management is locked to the super-admin-only 'manage-roles'
 * gate instead, see AppServiceProvider.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Developers\ApiKeyController;
use App\Http\Controllers\Developers\DashboardController;
use App\Http\Controllers\Developers\RequestLogController;
use App\Http\Controllers\Developers\StatsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:access-developers'])->prefix('developers')->name('developers.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])->name('index');

        Route::patch('/{key}/regenerate', [ApiKeyController::class, 'regenerate'])->name('regenerate');
    });

    Route::prefix('{key}')->group(function () {
        Route::get('requests', [RequestLogController::class, 'index'])->name('requests.index');
        Route::get('stats', [StatsController::class, 'index'])->name('stats.index');
    });
});
