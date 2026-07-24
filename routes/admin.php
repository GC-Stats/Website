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
use App\Http\Controllers\Admin\EmoteController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\GameMapController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\MatchStreamController;
use App\Http\Controllers\Admin\MatchVodController;
use App\Http\Controllers\Admin\NewsAuthorController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NewsMediaController;
use App\Http\Controllers\Admin\NewsPublisherController;
use App\Http\Controllers\Admin\PhaseQualificationController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\PointTypeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SanctionController;
use App\Http\Controllers\Admin\StreamChannelController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\Admin\TournamentOperationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\News\RoleController as PublisherRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

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

        Route::post('/', [TeamController::class, 'store'])
            ->middleware('can:teams.create')->name('store');

        Route::middleware(['can:teams.edit'])->group(function () {
            Route::put('/{team}', [TeamController::class, 'updateProfile'])->name('update');
            Route::put('/{team}/tags', [TeamController::class, 'updateTags'])->name('tags.update');
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
                Route::put('/', [TeamController::class, 'syncRoster'])->name('sync');
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

        Route::post('/', [PlayerController::class, 'store'])
            ->middleware('can:players.create')->name('store');

        Route::middleware(['can:players.edit'])->group(function () {
            Route::put('/{player}', [PlayerController::class, 'updateProfile'])->name('update');
            Route::put('/{player}/user', [PlayerController::class, 'linkUser'])->name('user.update');
            Route::delete('/{player}/user', [PlayerController::class, 'unlinkUser'])->name('user.destroy');
            Route::delete('/{player}/val-id', [PlayerController::class, 'resetValId'])->name('val-id.destroy');
            Route::delete('/{player}/discord-id', [PlayerController::class, 'resetDiscordId'])->name('discord-id.destroy');
            Route::prefix('{player}/logo')->name('logo.')->group(function () {
                Route::post('/', [PlayerController::class, 'updateLogo'])->name('update');
                Route::post('/history', [PlayerController::class, 'storeLogoHistory'])->name('history.store');
                Route::put('/history/{logo}', [PlayerController::class, 'updateLogoEntry'])->name('history.update');
                Route::delete('/history/{logo}', [PlayerController::class, 'destroyLogoEntry'])->name('history.destroy');
            });
            Route::prefix('{player}/team-history')->name('team-history.')->group(function () {
                Route::post('/', [PlayerController::class, 'storeTeamHistory'])->name('store');
                Route::put('/', [PlayerController::class, 'syncTeamHistory'])->name('sync');
            });
        });

        Route::put('/{player}/identifiers', [PlayerController::class, 'updateIdentifiers'])
            ->middleware('can:players.identifiers.manage')->name('identifiers.update');

        Route::delete('/{player}', [PlayerController::class, 'destroy'])
            ->middleware('can:players.delete')->name('destroy');
        Route::post('/{player}/merge', [PlayerController::class, 'merge'])
            ->middleware('can:players.merge')->name('merge.execute');
    });

    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::middleware(['can:tournaments.view'])->group(function () {
            Route::get('/', [TournamentController::class, 'index'])->name('index');
            Route::get('/create', [TournamentController::class, 'create'])->name('create');
            Route::get('/{tournament}', [TournamentController::class, 'show'])->name('show');
            Route::get('/{tournament}/edit', [TournamentController::class, 'edit'])->name('edit');
        });

        Route::post('/', [TournamentController::class, 'store'])
            ->middleware('can:tournaments.create')->name('store');
        Route::put('/{tournament}', [TournamentController::class, 'update'])
            ->middleware('can:tournaments.edit')->name('update');
        Route::delete('/{tournament}', [TournamentController::class, 'destroy'])
            ->middleware('can:tournaments.delete')->name('destroy');
        Route::patch('/{tournament}/toggle-active', [TournamentController::class, 'toggleActive'])
            ->middleware('can:tournaments.activate')->name('toggle-active');

        Route::middleware(['can:tournaments.teams.manage'])->group(function () {
            Route::post('/{tournament}/teams', [TournamentController::class, 'attachTeam'])->name('teams.store');
            Route::post('/{tournament}/teams/quick-create', [TournamentController::class, 'quickCreateTeam'])->name('teams.quick-create');
            Route::delete('/{tournament}/teams/{team}', [TournamentController::class, 'detachTeam'])->name('teams.destroy');
        });

        Route::get('/phases/search', [PhaseQualificationController::class, 'searchPhases'])
            ->middleware('can:tournaments.edit')->name('phases.search');
        Route::post('/{tournament}/phases/{phase}/qualifications', [PhaseQualificationController::class, 'store'])
            ->middleware('can:tournaments.edit')->name('phases.qualifications.store');
        // Shared by rank-based (phase) and match-outcome qualification rules — see
        // PhaseQualificationController::destroy() for the per-rule permission check.
        Route::delete('/{tournament}/qualifications/{qualification}', [PhaseQualificationController::class, 'destroy'])
            ->name('qualifications.destroy');
    });

    Route::prefix('point-types')->name('point-types.')->group(function () {
        Route::middleware(['can:tournaments.view'])->group(function () {
            Route::get('/', [PointTypeController::class, 'index'])->name('index');
            Route::get('/create', [PointTypeController::class, 'create'])->name('create');
            Route::get('/{pointType}/edit', [PointTypeController::class, 'edit'])->name('edit');
        });

        Route::middleware(['can:tournaments.edit'])->group(function () {
            Route::post('/', [PointTypeController::class, 'store'])->name('store');
            Route::put('/{pointType}', [PointTypeController::class, 'update'])->name('update');
            Route::delete('/{pointType}', [PointTypeController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('emotes')->name('emotes.')->group(function () {
        Route::middleware(['can:emotes.view'])->group(function () {
            Route::get('/', [EmoteController::class, 'index'])->name('index');
            Route::get('/create', [EmoteController::class, 'create'])->name('create');
            Route::get('/{emote}/edit', [EmoteController::class, 'edit'])->name('edit');
        });

        Route::post('/', [EmoteController::class, 'store'])
            ->middleware('can:emotes.create')->name('store');
        Route::put('/{emote}', [EmoteController::class, 'update'])
            ->middleware('can:emotes.edit')->name('update');
        Route::delete('/{emote}', [EmoteController::class, 'destroy'])
            ->middleware('can:emotes.delete')->name('destroy');
    });

    Route::prefix('tournaments/{tournament}/operations')->name('tournaments.operations.')->group(function () {
        Route::get('/', [TournamentOperationController::class, 'index'])->name('index');
        Route::post('/patch', [TournamentOperationController::class, 'patchAll'])
            ->middleware('can:operations.patch')->name('patch');
        Route::post('/bulk-create', [TournamentOperationController::class, 'bulkCreate'])
            ->middleware('can:operations.bulk-create')->name('bulk-create');
        Route::post('/cache-purge', [TournamentOperationController::class, 'purgeCache'])
            ->middleware('can:operations.cache-purge')->name('cache-purge');
    });

    Route::prefix('tournaments/{tournament}/matches')->name('matches.')->group(function () {
        Route::middleware(['can:matches.view'])->group(function () {
            Route::get('/', [MatchController::class, 'index'])->name('index');
            Route::get('/{match}', [MatchController::class, 'show'])->name('show');
            Route::get('/{match}/edit', [MatchController::class, 'edit'])->name('edit');
            Route::get('/{match}/veto', [MatchController::class, 'editVeto'])->name('veto.edit');
        });

        Route::post('/', [MatchController::class, 'store'])
            ->middleware('can:matches.create')->name('store');
        Route::put('/{match}', [MatchController::class, 'update'])
            ->middleware('can:matches.edit')->name('update');
        Route::delete('/{match}', [MatchController::class, 'destroy'])
            ->middleware('can:matches.delete')->name('destroy');
        Route::put('/{match}/veto', [MatchController::class, 'updateVeto'])
            ->middleware('can:matches.veto.edit')->name('veto.update');
        Route::delete('/{match}/reset-maps', [MatchController::class, 'resetMaps'])
            ->middleware('can:maps.reset')->name('reset-maps');
        Route::post('/{match}/import-wikicode', [MatchController::class, 'importWikicode'])
            ->middleware('can:matches.import')->name('import-wikicode');
        Route::post('/{match}/qualifications', [PhaseQualificationController::class, 'storeForMatch'])
            ->middleware('can:matches.edit')->name('qualifications.store');

        Route::prefix('{match}/streams')->name('streams.')->group(function () {
            Route::post('/', [MatchStreamController::class, 'store'])->name('store');
            Route::delete('/{channel}', [MatchStreamController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('{match}/vods')->name('vods.')->group(function () {
            Route::post('/', [MatchVodController::class, 'store'])->name('store');
            Route::delete('/{vod}', [MatchVodController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('{match}/maps')->name('maps.')->group(function () {
            Route::get('/{map}', [GameMapController::class, 'show'])
                ->middleware('can:maps.edit')->name('show');
            Route::put('/{map}', [GameMapController::class, 'update'])
                ->middleware('can:maps.edit')->name('update');
            Route::put('/{map}/stats', [GameMapController::class, 'updateStats'])
                ->middleware('can:maps.edit')->name('stats.update');
            Route::post('/{map}/fetch', [GameMapController::class, 'fetch'])
                ->middleware('can:maps.fetch')->name('fetch');
            Route::post('/{map}/renew', [GameMapController::class, 'renew'])
                ->middleware('can:maps.cache.renew')->name('renew');
            Route::post('/{map}/reset', [GameMapController::class, 'reset'])
                ->middleware('can:maps.reset')->name('reset');
            Route::delete('/{map}', [GameMapController::class, 'destroy'])
                ->middleware('can:maps.delete')->name('destroy');
        });
    });

    Route::get('matches/streams/search', [MatchStreamController::class, 'search'])->name('matches.streams.search');

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

    Route::prefix('streams')->name('streams.')->group(function () {
        Route::get('/', [StreamChannelController::class, 'index'])->name('index');
        Route::get('/create', [StreamChannelController::class, 'create'])->name('create');
        Route::post('/', [StreamChannelController::class, 'store'])->name('store');
        Route::get('/{channel}/edit', [StreamChannelController::class, 'edit'])->name('edit');
        Route::put('/{channel}', [StreamChannelController::class, 'update'])->name('update');
        Route::delete('/{channel}', [StreamChannelController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('streams/matches')->name('streams.matches.')->group(function () {
        Route::get('/', [MatchStreamController::class, 'index'])->name('index');
        Route::get('/create', [MatchStreamController::class, 'create'])->name('create');
        Route::get('/search/tournaments', [MatchStreamController::class, 'searchTournaments'])->name('search-tournaments');
        Route::get('/search/matches', [MatchStreamController::class, 'searchMatchesInTournament'])->name('search-matches');
        Route::post('/link', [MatchStreamController::class, 'linkMany'])->name('link');
    });

    // Same "liste tout" + wizard pattern as streams/matches above, for VODs
    // (see Admin\MatchVodController) — no separate channel CRUD here since
    // a VOD isn't a reusable entity (see App\Models\Vod's docblock).
    Route::prefix('vods')->name('vods.')->group(function () {
        Route::get('/', [MatchVodController::class, 'index'])->name('index');
        Route::get('/create', [MatchVodController::class, 'create'])->name('create');
        Route::get('/search/tournaments', [MatchVodController::class, 'searchTournaments'])->name('search-tournaments');
        Route::get('/search/matches', [MatchVodController::class, 'searchMatchesInTournament'])->name('search-matches');
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
