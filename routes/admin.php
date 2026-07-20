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

use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\NewsAuthorController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NewsMediaController;
use App\Http\Controllers\Admin\NewsPublisherController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SanctionController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\News\RoleController as PublisherRoleController;
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

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->middleware('can:analytics.view')->name('analytics.index');

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

    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::get('/create', [NewsController::class, 'create'])->name('create');
        Route::get('/relations/search', [NewsController::class, 'searchRelations'])->name('relations.search');
        Route::post('/', [NewsController::class, 'store'])->name('store');
        Route::get('/{article}/edit', [NewsController::class, 'edit'])->name('edit');
        Route::put('/{article}', [NewsController::class, 'update'])->name('update');
        Route::delete('/{article}', [NewsController::class, 'destroy'])->name('destroy');
        Route::post('/{article}/publish', [NewsController::class, 'publish'])->name('publish');
        Route::post('/{article}/archive', [NewsController::class, 'archive'])->name('archive');

        Route::middleware(['can:news.edit'])->group(function () {
            Route::post('/{article}/feature', [NewsController::class, 'toggleFeature'])->name('feature');
            Route::post('/{article}/show-on-home', [NewsController::class, 'toggleShowOnHome'])->name('show-on-home');
        });

        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [NewsMediaController::class, 'index'])->name('index');
            Route::post('/', [NewsMediaController::class, 'store'])->name('store');
            Route::put('/{image}/link', [NewsMediaController::class, 'link'])->name('link');
            Route::put('/{article}/cover/{image}', [NewsMediaController::class, 'setCover'])->name('cover.update');
            Route::delete('/{image}', [NewsMediaController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('publishers')->name('publishers.')->group(function () {
            // Site admins see the full list; a publisher-only member is
            // self-redirected to their own publisher — see
            // Admin\NewsPublisherController::index.
            Route::get('/', [NewsPublisherController::class, 'index'])->name('index');

            Route::middleware(['can:news.publishers.edit'])->group(function () {
                Route::post('/', [NewsPublisherController::class, 'store'])->name('store');
            });

            Route::get('/{publisher}', [NewsPublisherController::class, 'show'])->name('show');
            Route::put('/{publisher}', [NewsPublisherController::class, 'update'])->name('update');
            Route::post('/{publisher}/logo', [NewsPublisherController::class, 'updateLogo'])->name('logo.update');

            Route::middleware(['can:news.publishers.edit'])->group(function () {
                Route::put('/{publisher}/max-permissions', [NewsPublisherController::class, 'updateMaxPermissions'])->name('max-permissions.update');
            });
            Route::middleware(['can:news.publishers.owner.manage'])->group(function () {
                Route::post('/{publisher}/owner', [NewsPublisherController::class, 'assignOwner'])->name('owner.store');
                Route::delete('/{publisher}/owner/{user}', [NewsPublisherController::class, 'removeOwner'])->name('owner.destroy');
            });
            Route::delete('/{publisher}', [NewsPublisherController::class, 'destroy'])
                ->middleware('can:news.publishers.delete')->name('destroy');

            Route::prefix('{publisher}/roles')->name('roles.')
                ->middleware(['publisher.permission-context', 'can:publisher.roles.manage'])
                ->group(function () {
                    Route::get('/', [PublisherRoleController::class, 'index'])->name('index');
                    Route::post('/', [PublisherRoleController::class, 'store'])->name('store');
                    Route::get('/{role}', [PublisherRoleController::class, 'show'])->name('show');
                    Route::put('/{role}', [PublisherRoleController::class, 'update'])->name('update');
                    Route::delete('/{role}', [PublisherRoleController::class, 'destroy'])->name('destroy');

                    Route::post('/{role}/members', [PublisherRoleController::class, 'addMember'])->name('members.store');
                    Route::delete('/{role}/members/{user}', [PublisherRoleController::class, 'removeMember'])->name('members.destroy');
                });
        });

        Route::prefix('authors')->name('authors.')->group(function () {
            Route::get('/', [NewsAuthorController::class, 'index'])->name('index');


            Route::post('/', [NewsAuthorController::class, 'store'])->name('store');

            Route::get('/{author}', [NewsAuthorController::class, 'show'])->name('show');
            Route::put('/{author}', [NewsAuthorController::class, 'update'])->name('update');
            Route::post('/{author}/logo', [NewsAuthorController::class, 'updateLogo'])->name('logo.update');

            Route::delete('/{author}', [NewsAuthorController::class, 'destroy'])
                ->middleware('can:news.authors.delete')->name('destroy');
        });
    });

    Route::prefix('users')->name('users.')->middleware(['can:users.view'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
    });

    Route::prefix('about')->name('about.')->group(function () {
        Route::get('/', [AboutController::class, 'index'])
            ->middleware('can:about.view')->name('index');

        Route::middleware(['can:about.manage'])->group(function () {
            Route::put('/sections/{key}', [AboutController::class, 'saveSection'])->name('sections.update');

            Route::post('/team', [AboutController::class, 'storeMember'])->name('team.store');
            Route::put('/team/{member}', [AboutController::class, 'updateMember'])->name('team.update');
            Route::delete('/team/{member}', [AboutController::class, 'destroyMember'])->name('team.destroy');
            Route::post('/team/{member}/photo', [AboutController::class, 'uploadMemberPhoto'])->name('team.photo');

            Route::post('/projects', [AboutController::class, 'storeProject'])->name('projects.store');
            Route::put('/projects/{project}', [AboutController::class, 'updateProject'])->name('projects.update');
            Route::delete('/projects/{project}', [AboutController::class, 'destroyProject'])->name('projects.destroy');
            Route::post('/projects/{project}/logo', [AboutController::class, 'uploadProjectLogo'])->name('projects.logo');
        });
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])
            ->middleware('can:finance.view')->name('index');

        Route::middleware(['can:finance.manage'])->group(function () {
            Route::post('/', [FinanceController::class, 'store'])->name('store');
            Route::patch('/{entry}', [FinanceController::class, 'update'])->name('update');
            Route::delete('/{entry}', [FinanceController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])
            ->middleware('can:api-keys.view')->name('index');

        Route::middleware(['can:api-keys.manage'])->group(function () {
            Route::post('/', [ApiKeyController::class, 'store'])->name('store');
            Route::patch('/{key}', [ApiKeyController::class, 'update'])->name('update');
            Route::patch('/{key}/toggle', [ApiKeyController::class, 'toggleStatus'])->name('toggle');
            Route::patch('/{key}/regenerate', [ApiKeyController::class, 'regenerate'])->name('regenerate');
        });
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
