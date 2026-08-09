<?php

/**
 * GC-Stats — Organization dashboard routes
 *
 * The organization's own dedicated space (see resources/views/organization/
 * layout.blade.php) — reachable by anyone holding a role on the given
 * organization, not just site admins (see App\Services\OrganizationAccessService,
 * checked inline in DashboardController rather than route middleware, since
 * "can view" also allows a site admin with organizations.view through).
 * Role management reuses Organization\RoleController — the same controller
 * backing admin.organizations.roles.* (routes/admin.php) — which picks
 * between the admin and dashboard view/route wrapper based on which route
 * matched (see its isDashboard()/routePrefix()/viewName() helpers), so the
 * business logic and the shared partials (resources/views/organizations/
 * roles/_index.blade.php, _show.blade.php) are never duplicated. News
 * management mirrors the exact same pattern via Admin\NewsController and
 * resources/views/news/*.blade.php, this time shared with the flat
 * admin.news.* routes instead of a per-organization admin route group —
 * see that controller's docblock.
 *
 * Deliberately prefixed with `dashboard/organizations/...` — matching the
 * standalone /admin and /developers areas — rather than nesting under the
 * public `/organization/{organization}/{slug?}` path (routes/web.php):
 * that route's optional trailing slug segment means any suffix appended to
 * a public organization URL (e.g. a user hand-editing
 * /organization/5/some-org-name into .../dashboard) lands on a URL with an
 * extra segment that matches neither route and 404s. A fully separate
 * prefix makes that collision structurally impossible instead of relying
 * on route registration order to keep the two apart.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Admin\ApiKeyController as OrganizationDashboardApiKeyController;
use App\Http\Controllers\Admin\MatchStreamController as OrganizationDashboardMatchStreamController;
use App\Http\Controllers\Admin\MatchVodController as OrganizationDashboardMatchVodController;
use App\Http\Controllers\Admin\NewsAuthorController as OrganizationDashboardNewsAuthorController;
use App\Http\Controllers\Admin\NewsController as OrganizationDashboardNewsController;
use App\Http\Controllers\Admin\NewsMediaController as OrganizationDashboardNewsMediaController;
use App\Http\Controllers\Admin\StreamChannelController as OrganizationDashboardStreamChannelController;
use App\Http\Controllers\Organization\DashboardController;
use App\Http\Controllers\Organization\ExperienceController as OrganizationDashboardExperienceController;
use App\Http\Controllers\Organization\RoleController as OrganizationDashboardRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization.permission-context'])
    ->prefix('dashboard/organizations/{organization}')
    ->name('organization-dashboard.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/edit', [DashboardController::class, 'edit'])->name('edit');
        Route::put('/', [DashboardController::class, 'updateProfile'])->name('update');
        Route::post('/logo', [DashboardController::class, 'updateLogo'])->name('logo.update');
        Route::put('/staff', [DashboardController::class, 'syncStaff'])->name('staff.sync');

        Route::prefix('experience')->name('experience.')->group(function () {
            Route::get('/', [OrganizationDashboardExperienceController::class, 'index'])->name('index');
            Route::get('/tournaments/{tournament}', [OrganizationDashboardExperienceController::class, 'tournament'])->name('tournaments.show');
            Route::put('/tournaments/{tournament}', [OrganizationDashboardExperienceController::class, 'syncTournament'])->name('tournaments.sync');
            Route::get('/tournaments/{tournament}/matches/{match}', [OrganizationDashboardExperienceController::class, 'match'])->name('matches.show');
            Route::put('/tournaments/{tournament}/matches/{match}', [OrganizationDashboardExperienceController::class, 'syncMatch'])->name('matches.sync');
        });

        Route::middleware(['can:organization.roles.manage'])->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [OrganizationDashboardRoleController::class, 'index'])->name('index');
            Route::post('/', [OrganizationDashboardRoleController::class, 'store'])->name('store');
            Route::get('/{role}', [OrganizationDashboardRoleController::class, 'show'])->name('show');
            Route::put('/{role}', [OrganizationDashboardRoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [OrganizationDashboardRoleController::class, 'destroy'])->name('destroy');

            Route::post('/{role}/members', [OrganizationDashboardRoleController::class, 'addMember'])->name('members.store');
            Route::delete('/{role}/members/{user}', [OrganizationDashboardRoleController::class, 'removeMember'])->name('members.destroy');
        });

        Route::middleware(['can:organization.news.view'])->prefix('news')->name('news.')->group(function () {
            Route::get('/', [OrganizationDashboardNewsController::class, 'index'])->name('index');
            Route::get('/create', [OrganizationDashboardNewsController::class, 'create'])->name('create');
            Route::post('/', [OrganizationDashboardNewsController::class, 'store'])->name('store');
            Route::get('/{article}/edit', [OrganizationDashboardNewsController::class, 'edit'])->name('edit');
            Route::put('/{article}', [OrganizationDashboardNewsController::class, 'update'])->name('update');
            Route::delete('/{article}', [OrganizationDashboardNewsController::class, 'destroy'])->name('destroy');
            Route::post('/{article}/publish', [OrganizationDashboardNewsController::class, 'publish'])->name('publish');
            Route::post('/{article}/archive', [OrganizationDashboardNewsController::class, 'archive'])->name('archive');
            Route::post('/{article}/validate', [OrganizationDashboardNewsController::class, 'markValidated'])->name('validate');

            Route::prefix('{article}/comments')->name('comments.')->group(function () {
                Route::post('/', [OrganizationDashboardNewsController::class, 'storeComment'])->name('store');
                Route::put('/{comment}', [OrganizationDashboardNewsController::class, 'resolveComment'])->name('resolve');
                Route::delete('/{comment}', [OrganizationDashboardNewsController::class, 'destroyComment'])->name('destroy');
            });

            Route::prefix('media')->name('media.')->group(function () {
                Route::get('/', [OrganizationDashboardNewsMediaController::class, 'index'])->name('index');
            });

            // The author profile itself is personal, not organization data
            // (see Admin\NewsAuthorController's docblock) — these routes
            // only exist here so a dashboard news contributor never has to
            // leave the dashboard to manage their own byline.
            Route::prefix('author')->name('author.')->group(function () {
                Route::get('/', [OrganizationDashboardNewsAuthorController::class, 'myProfile'])->name('my');
                Route::post('/', [OrganizationDashboardNewsAuthorController::class, 'store'])->name('store');
                Route::get('/{author}', [OrganizationDashboardNewsAuthorController::class, 'show'])->name('show');
                Route::put('/{author}', [OrganizationDashboardNewsAuthorController::class, 'update'])->name('update');
                Route::post('/{author}/logo', [OrganizationDashboardNewsAuthorController::class, 'updateLogo'])->name('logo.update');
            });
        });

        Route::middleware(['can:organization.streams.view'])->prefix('streams')->name('streams.')->group(function () {
            Route::get('/', [OrganizationDashboardStreamChannelController::class, 'index'])->name('index');
            Route::get('/create', [OrganizationDashboardStreamChannelController::class, 'create'])->name('create');
            Route::post('/', [OrganizationDashboardStreamChannelController::class, 'store'])->name('store');
            Route::get('/{channel}/edit', [OrganizationDashboardStreamChannelController::class, 'edit'])->name('edit');
            Route::put('/{channel}', [OrganizationDashboardStreamChannelController::class, 'update'])->name('update');
            Route::delete('/{channel}', [OrganizationDashboardStreamChannelController::class, 'destroy'])->name('destroy');

            // "Liste tout" (matches with a linked stream) + the link wizard
            // — see Admin\MatchStreamController's docblock for why only
            // index()/create()/linkMany() are dual-context.
            Route::prefix('matches')->name('matches.')->group(function () {
                Route::get('/', [OrganizationDashboardMatchStreamController::class, 'index'])->name('index');
                Route::get('/create', [OrganizationDashboardMatchStreamController::class, 'create'])->name('create');
                Route::post('/link', [OrganizationDashboardMatchStreamController::class, 'linkMany'])->name('link');
            });
        });

        Route::middleware(['can:organization.vods.link'])->prefix('vods')->name('vods.')->group(function () {
            Route::get('/', [OrganizationDashboardMatchVodController::class, 'index'])->name('index');
            Route::get('/create', [OrganizationDashboardMatchVodController::class, 'create'])->name('create');
        });

        // An organization can only view its own keys and regenerate them —
        // creating a key or changing its owner/name/rate-limit/status is
        // admin-only (see Admin\ApiKeyController's docblock), so no
        // store/update/toggle route exists here.
        Route::prefix('api-keys')->name('api-keys.')->group(function () {
            Route::get('/', [OrganizationDashboardApiKeyController::class, 'index'])
                ->middleware('can:organization.api-keys.view')->name('index');

            Route::patch('/{key}/regenerate', [OrganizationDashboardApiKeyController::class, 'regenerate'])
                ->middleware('can:organization.api-keys.manage')->name('regenerate');
        });
    });
