<?php

/**
 * GC-Stats — Personal (org-less) author dashboard routes
 *
 * The equivalent of routes/organization.php for an author who isn't attached
 * to any organization: reachable by anyone holding the site-wide 'news.author'
 * permission (see App\Support\AdminPermissions), gated at the group level
 * since there's no {organization} to check a role against — no
 * SetOrganizationPermissionContext middleware either, since 'news.author' is
 * a plain web-guard permission, not organization-scoped.
 *
 * News/media/author management reuses the exact same
 * Admin\NewsController/NewsMediaController/NewsAuthorController as
 * admin.news.* and organization-dashboard.news.* — see those controllers'
 * isDashboard()/isPersonalDashboard()/routePrefix()/viewName() helpers. Only
 * the subset of news actions that make sense without an organization is
 * exposed here: no validate/comments (review workflow is organization-only,
 * see NewsController's docblock) and no roles/streams/vods/api-keys/
 * experience (all organization concepts with no personal equivalent).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Admin\NewsAuthorController as PersonalDashboardNewsAuthorController;
use App\Http\Controllers\Admin\NewsController as PersonalDashboardNewsController;
use App\Http\Controllers\Admin\NewsMediaController as PersonalDashboardNewsMediaController;
use App\Http\Controllers\PersonalDashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:news.author'])
    ->prefix('dashboard/me')
    ->name('personal-dashboard.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        Route::prefix('news')->name('news.')->group(function () {
            Route::get('/', [PersonalDashboardNewsController::class, 'index'])->name('index');
            Route::get('/create', [PersonalDashboardNewsController::class, 'create'])->name('create');
            Route::post('/', [PersonalDashboardNewsController::class, 'store'])->name('store');
            Route::get('/{article}/edit', [PersonalDashboardNewsController::class, 'edit'])->name('edit');
            Route::put('/{article}', [PersonalDashboardNewsController::class, 'update'])->name('update');
            Route::delete('/{article}', [PersonalDashboardNewsController::class, 'destroy'])->name('destroy');
            Route::post('/{article}/publish', [PersonalDashboardNewsController::class, 'publish'])->name('publish');
            Route::post('/{article}/archive', [PersonalDashboardNewsController::class, 'archive'])->name('archive');

            Route::prefix('media')->name('media.')->group(function () {
                Route::get('/', [PersonalDashboardNewsMediaController::class, 'index'])->name('index');
            });

            // The author profile itself is personal data either way — these
            // routes exist so a lone author never has to leave the dashboard
            // to manage their own byline, mirroring
            // organization-dashboard.news.author.* (see routes/organization.php).
            Route::prefix('author')->name('author.')->group(function () {
                Route::get('/', [PersonalDashboardNewsAuthorController::class, 'myProfile'])->name('my');
                Route::post('/', [PersonalDashboardNewsAuthorController::class, 'store'])->name('store');
                Route::get('/{author}', [PersonalDashboardNewsAuthorController::class, 'show'])->name('show');
                Route::put('/{author}', [PersonalDashboardNewsAuthorController::class, 'update'])->name('update');
                Route::post('/{author}/logo', [PersonalDashboardNewsAuthorController::class, 'updateLogo'])->name('logo.update');
            });
        });
    });
