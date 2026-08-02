<?php

/**
 * GC-Stats — Data Explorer routes
 *
 * The whole feature is currently early access: only users with the
 * per-user data_explorer_enabled flag (toggled from the admin panel, see
 * Admin\DataExplorerController and EnsureDataExplorerIsEnabled) can reach
 * any of the routes below — everyone else gets an early-access
 * placeholder instead. That same flag also governs use of the platform's
 * own API key once inside (see DataExplorerQuotaService::claimRequestSlot()).
 * Anyone without it needs their own linked key from the dedicated
 * settings page below — except settings/docs aren't reachable either
 * while access is closed.
 *
 * Both execute() routes carry a per-minute throttle on top of the hourly
 * one — the hourly cap alone doesn't stop a burst (e.g. a script firing 15
 * requests in the first few seconds of the hour); the per-minute cap is
 * what actually protects Cube/the DB from someone pulling a large chunk of
 * data in a handful of seconds. The query builder gets the tighter minute
 * cap of the two: it has no LLM latency slowing requests down, so it's the
 * faster path to hammer the backend with.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\DataExplorer\BuilderController;
use App\Http\Controllers\DataExplorer\DocsController;
use App\Http\Controllers\DataExplorer\QueryController;
use App\Http\Controllers\DataExplorer\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'data-explorer.enabled'])->prefix('data-explorer')->name('data-explorer.')->group(function () {
    Route::get('/', [QueryController::class, 'index'])->name('index');

    Route::get('/builder', [BuilderController::class, 'index'])->name('builder');

    Route::get('/docs', [DocsController::class, 'index'])->name('docs');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');

    Route::middleware(['not-sanctioned'])->group(function () {
        Route::post('/execute', [QueryController::class, 'execute'])
            ->middleware(['throttle:8,1', 'throttle:15,60'])->name('execute');

        Route::post('/builder/execute', [BuilderController::class, 'execute'])
            ->middleware(['throttle:10,1', 'throttle:30,60'])->name('builder.execute');

        Route::put('/settings/key', [SettingsController::class, 'store'])->name('settings.key.update');
        Route::put('/settings/key/activate', [SettingsController::class, 'activate'])->name('settings.key.activate');
        Route::delete('/settings/key/{provider}', [SettingsController::class, 'destroy'])->name('settings.key.destroy');
    });
});
