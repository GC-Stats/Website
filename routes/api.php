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
        Route::get('/stats', [ApiStatsController::class, 'index']);

        Route::get('/analytics/summary', [ApiAnalyticsController::class, 'summary']);
        Route::get('/analytics/hourly', [ApiAnalyticsController::class, 'hourly']);
        Route::get('/analytics/top-pages', [ApiAnalyticsController::class, 'topPages']);

        Route::get('/api-keys', [ApiApiKeyController::class, 'index']);
        Route::post('/api-keys', [ApiApiKeyController::class, 'store']);
        Route::patch('/api-keys/{id}', [ApiApiKeyController::class, 'update']);
        Route::patch('/api-keys/{id}/toggle', [ApiApiKeyController::class, 'toggleStatus']);
        Route::patch('/api-keys/{id}/regenerate', [ApiApiKeyController::class, 'regenerate']);

        Route::get('/api-request-logs/summary', [ApiRequestLogController::class, 'summary']);
        Route::get('/api-request-logs/keys/{id}', [ApiRequestLogController::class, 'forKey']);

        Route::get('/tournaments', [ApiTournamentController::class, 'index']);
        Route::get('/tournaments/meta', [ApiTournamentController::class, 'meta']);
        Route::get('/tournaments/{id}', [ApiTournamentController::class, 'show']);
        Route::post('/tournaments', [ApiTournamentController::class, 'store']);
        Route::patch('/tournaments/{id}', [ApiTournamentController::class, 'update']);
        Route::delete('/tournaments/{id}', [ApiTournamentController::class, 'destroy']);
        Route::get('/tournaments/{id}/teams', [ApiTournamentController::class, 'teams']);
        Route::post('/tournaments/{id}/teams', [ApiTournamentController::class, 'attachTeam']);
        Route::delete('/tournaments/{id}/teams/{team_id}', [ApiTournamentController::class, 'detachTeam']);

        Route::get('/tournaments/{id}/matches', [ApiMatchController::class, 'index']);
        Route::post('/tournaments/{id}/matches', [ApiMatchController::class, 'store']);
        Route::get('/matches/{id}', [ApiMatchController::class, 'show']);
        Route::patch('/matches/{id}', [ApiMatchController::class, 'update']);
        Route::delete('/matches/{id}', [ApiMatchController::class, 'destroy']);
        Route::put('/matches/{id}/veto', [ApiMatchController::class, 'saveVeto']);
        Route::delete('/matches/{id}/reset-maps', [ApiMatchController::class, 'resetMaps']);

        Route::get('/maps/{id}/fetch', [ApiGameMapController::class, 'fetch']);
        Route::get('/matches/{id}/maps/{map_id}', [ApiGameMapController::class, 'show']);
        Route::patch('/maps/{id}', [ApiGameMapController::class, 'update']);
        Route::put('/maps/{id}/stats', [ApiGameMapController::class, 'storeStats']);
        Route::post('/maps/{id}/assign-players', [ApiGameMapController::class, 'assignPlayers']);
        Route::delete('/maps/{id}/reset', [ApiGameMapController::class, 'reset']);

        Route::get('/players', [ApiPlayerController::class, 'index']);
        Route::post('/players', [ApiPlayerController::class, 'store']);
        Route::get('/players/by-vlr/{vlrId}', [ApiPlayerController::class, 'showByVlrId']);
        Route::get('/players/{id}', [ApiPlayerController::class, 'show']);
        Route::patch('/players/{id}', [ApiPlayerController::class, 'update']);
        Route::patch('/players/{id}/team', [ApiPlayerController::class, 'updateTeam']);
        Route::post('/players/{id}/reset-val-id', [ApiPlayerController::class, 'resetValId']);
        Route::post('/players/{id}/reset-discord-id', [ApiPlayerController::class, 'resetDiscordId']);
        Route::get('/players/{id}/team-history', [ApiPlayerController::class, 'teamHistory']);
        Route::put('/players/{id}/team-history', [ApiPlayerController::class, 'saveTeamHistory']);

        Route::get('/players/{id}/logos', [ApiPlayerLogoController::class, 'index']);
        Route::post('/players/{id}/logos', [ApiPlayerLogoController::class, 'upload']);
        Route::post('/players/logo/accept', [ApiPlayerLogoController::class, 'accept']);
        Route::post('/players/logo/refuse', [ApiPlayerLogoController::class, 'refuse']);
        Route::delete('/players/logo/{uuid}', [ApiPlayerLogoController::class, 'delete']);

        Route::get('/teams', [ApiTeamController::class, 'index']);
        Route::post('/teams', [ApiTeamController::class, 'store']);
        Route::get('/teams/by-vlr/{vlrId}', [ApiTeamController::class, 'showByVlrId']);
        Route::get('/teams/{id}', [ApiTeamController::class, 'show']);
        Route::patch('/teams/{id}', [ApiTeamController::class, 'update']);
        Route::get('/teams/{id}/roster', [ApiTeamController::class, 'roster']);
        Route::put('/teams/{id}/roster', [ApiTeamController::class, 'saveRoster']);
        Route::get('/teams/{id}/roster-history', [ApiTeamController::class, 'rosterHistory']);
        Route::delete('/teams/{id}/roster-history/{entry}', [ApiTeamController::class, 'deleteRosterEntry']);

        Route::get('/teams/{id}/logos', [ApiTeamLogoController::class, 'index']);
        Route::post('/teams/{id}/logos', [ApiTeamLogoController::class, 'upload']);
        Route::post('/teams/logo/accept', [ApiTeamLogoController::class, 'accept']);
        Route::post('/teams/logo/refuse', [ApiTeamLogoController::class, 'refuse']);
        Route::delete('/teams/logo/{uuid}', [ApiTeamLogoController::class, 'delete']);

        Route::get('/tournaments/{id}/logos', [ApiTournamentLogoController::class, 'index']);
        Route::post('/tournaments/{id}/logos', [ApiTournamentLogoController::class, 'upload']);
        Route::post('/tournaments/logo/accept', [ApiTournamentLogoController::class, 'accept']);
        Route::post('/tournaments/logo/refuse', [ApiTournamentLogoController::class, 'refuse']);
        Route::delete('/tournaments/logo/{uuid}', [ApiTournamentLogoController::class, 'delete']);

        Route::get('/about', [ApiAboutController::class, 'index']);
        Route::patch('/about/sections/{key}', [ApiAboutController::class, 'updateSection']);

        Route::post('/about/team', [ApiAboutController::class, 'storeMember']);
        Route::patch('/about/team/{id}', [ApiAboutController::class, 'updateMember']);
        Route::post('/about/team/{id}/upload', [ApiAboutController::class, 'uploadMemberPhoto']);
        Route::delete('/about/team/{id}', [ApiAboutController::class, 'destroyMember']);

        Route::post('/about/projects', [ApiAboutController::class, 'storeProject']);
        Route::patch('/about/projects/{id}', [ApiAboutController::class, 'updateProject']);
        Route::post('/about/projects/{id}/upload', [ApiAboutController::class, 'uploadProjectLogo']);
        Route::delete('/about/projects/{id}', [ApiAboutController::class, 'destroyProject']);

        Route::get('/news/authors', [ApiNewsAuthorController::class, 'index']);
        Route::post('/news/authors', [ApiNewsAuthorController::class, 'store']);
        Route::get('/news/authors/{id}/logo', [ApiAuthorLogoController::class, 'index']);
        Route::post('/news/authors/{id}/logo', [ApiAuthorLogoController::class, 'upload']);
        Route::post('/news/authors/logo/accept', [ApiAuthorLogoController::class, 'accept']);
        Route::post('/news/authors/logo/refuse', [ApiAuthorLogoController::class, 'refuse']);
        Route::delete('/news/authors/logo/{uuid}', [ApiAuthorLogoController::class, 'delete']);
        Route::get('/news/authors/{id}', [ApiNewsAuthorController::class, 'show']);
        Route::patch('/news/authors/{id}', [ApiNewsAuthorController::class, 'update']);
        Route::delete('/news/authors/{id}', [ApiNewsAuthorController::class, 'destroy']);

        Route::get('/news/publishers', [ApiNewsPublisherController::class, 'index']);
        Route::post('/news/publishers', [ApiNewsPublisherController::class, 'store']);
        Route::get('/news/publishers/{id}/logo', [ApiPublisherLogoController::class, 'index']);
        Route::post('/news/publishers/{id}/logo', [ApiPublisherLogoController::class, 'upload']);
        Route::post('/news/publishers/logo/accept', [ApiPublisherLogoController::class, 'accept']);
        Route::post('/news/publishers/logo/refuse', [ApiPublisherLogoController::class, 'refuse']);
        Route::delete('/news/publishers/logo/{uuid}', [ApiPublisherLogoController::class, 'delete']);
        Route::get('/news/publishers/{id}', [ApiNewsPublisherController::class, 'show']);
        Route::patch('/news/publishers/{id}', [ApiNewsPublisherController::class, 'update']);
        Route::delete('/news/publishers/{id}', [ApiNewsPublisherController::class, 'destroy']);

        Route::get('/news', [ApiNewsController::class, 'index']);
        Route::post('/news', [ApiNewsController::class, 'store']);
        Route::get('/news/{id}', [ApiNewsController::class, 'show']);
        Route::patch('/news/{id}', [ApiNewsController::class, 'update']);
        Route::delete('/news/{id}', [ApiNewsController::class, 'destroy']);
        Route::post('/news/{id}/publish', [ApiNewsController::class, 'publish']);
        Route::post('/news/{id}/unpublish', [ApiNewsController::class, 'unpublish']);
        Route::put('/news/{id}/relations', [ApiNewsController::class, 'syncRelations']);
        Route::get('/news/{id}/images', [ApiNewsImageController::class, 'index']);

        Route::post('/news/images/upload', [ApiNewsImageController::class, 'upload']);
        Route::post('/news/images/link', [ApiNewsImageController::class, 'link']);
        Route::delete('/news/images/{uuid}', [ApiNewsImageController::class, 'delete']);

        Route::get('/finance', [ApiFinanceController::class, 'index']);
        Route::post('/finance', [ApiFinanceController::class, 'store']);
        Route::patch('/finance/{id}', [ApiFinanceController::class, 'update']);
        Route::delete('/finance/{id}', [ApiFinanceController::class, 'destroy']);
    });
