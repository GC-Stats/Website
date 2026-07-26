<?php

/**
 * GC-Stats — Internal API routes
 *
 * Defines the internal API endpoints (under /internal, protected by the
 * internal.service middleware) used to fetch and update player/team data
 * and logos from trusted internal services.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Api\ApiAboutController;
use App\Http\Controllers\Api\ApiAnalyticsController;
use App\Http\Controllers\Api\ApiApiKeyController;
use App\Http\Controllers\Api\ApiAuthorLogoController;
use App\Http\Controllers\Api\ApiFinanceController;
use App\Http\Controllers\Api\ApiGameMapController;
use App\Http\Controllers\Api\ApiMatchController;
use App\Http\Controllers\Api\ApiNewsAuthorController;
use App\Http\Controllers\Api\ApiNewsController;
use App\Http\Controllers\Api\ApiNewsImageController;
use App\Http\Controllers\Api\ApiNewsPublisherController;
use App\Http\Controllers\Api\ApiPlayerController;
use App\Http\Controllers\Api\ApiPlayerLogoController;
use App\Http\Controllers\Api\ApiPublisherLogoController;
use App\Http\Controllers\Api\ApiRequestLogController;
use App\Http\Controllers\Api\ApiStatsController;
use App\Http\Controllers\Api\ApiTeamController;
use App\Http\Controllers\Api\ApiTeamLogoController;
use App\Http\Controllers\Api\ApiTournamentController;
use App\Http\Controllers\Api\ApiTournamentLogoController;


Route::prefix('internal')
    ->middleware(['internal.service'])
    ->group(function () {
        Route::get('/players/{id}', [ApiPlayerController::class, 'show']);
        Route::patch('/players/{id}', [ApiPlayerController::class, 'update']);

        Route::post('/players/{id}/upload', [ApiPlayerLogoController::class, 'upload']);
        Route::post('/players/logo/accept', [ApiPlayerLogoController::class, 'accept']);
        Route::post('/players/logo/refuse', [ApiPlayerLogoController::class, 'refuse']);

        Route::get('/teams/{id}', [ApiTeamController::class, 'show']);
        Route::patch('/teams/{id}', [ApiTeamController::class, 'update']);

        Route::post('/teams/{id}/upload', [ApiTeamLogoController::class, 'upload']);
        Route::post('/teams/logo/accept', [ApiTeamLogoController::class, 'accept']);
        Route::post('/teams/logo/refuse', [ApiTeamLogoController::class, 'refuse']);
    });
