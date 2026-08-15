<?php

/**
 * GC-Stats — Web routes
 *
 * Defines the public-facing routes for the site: homepage, matches,
 * players, teams, tournaments, static/legal pages and health check.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ApiKeyRevealController;
use App\Http\Controllers\Public\FinanceController;
use App\Http\Controllers\Public\ForumController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MatchController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\PlayerController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\TeamController;
use App\Http\Controllers\Public\ThemePreferenceController;
use App\Http\Controllers\Public\TournamentController;
use App\Http\Controllers\Public\TransparencyController;
use App\Http\Controllers\Public\UserProfileController;
use App\Http\Controllers\Public\WidgetController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/developers.php';
require __DIR__.'/data_explorer.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'version' => config('app.version')]);
});

Route::middleware(['static.cache:2592000'])->group(function () {
    Route::get('/terms', function () {
        return view('public.legal.terms');
    })->name('terms');

    Route::get('/legal', function () {
        return view('public.legal.legal');
    })->name('legal');

    Route::get('/privacy', function () {
        return view('public.legal.privacy');
    })->name('privacy');

    Route::get('/data', function () {
        return view('public.data');
    })->name('data');

    Route::get('/takedown', function () {
        return view('public.legal.takedown');
    })->name('takedown');

    Route::get('/help/edit_page', function () {
        return view('public.help/edit_page');
    })->name('help.edit_page');

    Route::get('/help/add_tournament', function () {
        return view('public.help/add_tournament');
    })->name('help.add_tournament');

    Route::get('/developers-doc', function () {
        return view('public.developers');
    })->name('developers');
});

Route::middleware(['static.cache:300'])->group(function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/transparency', [TransparencyController::class, 'index'])->name('transparency');
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
});

Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
Route::prefix('/tournaments/{tournament}/{slug?}')->name('tournaments.')->group(function () {
    Route::get('/', [TournamentController::class, 'show'])->name('show');
    Route::get('/matches', [TournamentController::class, 'matches'])->name('matches');
    Route::get('/stats', [TournamentController::class, 'stats'])->name('stats');
    Route::get('/maps', [TournamentController::class, 'maps'])->name('maps');
});

Route::prefix('/team/{team}/{slug?}')->name('teams.')->group(function () {
    Route::get('/history', [TeamController::class, 'history'])->name('history');
    Route::get('/matches', [TeamController::class, 'matches'])->name('matches');
    Route::get('/maps', [TeamController::class, 'maps'])->name('maps');
    Route::get('/', [TeamController::class, 'index'])->name('show');
});

Route::prefix('/player/{player}/{slug?}')->name('players.')->group(function () {
    Route::get('/history', [PlayerController::class, 'history'])->name('history');
    Route::get('/matches', [PlayerController::class, 'matches'])->name('matches');
    Route::get('/stats', [PlayerController::class, 'stats'])->name('stats');
    Route::get('/', [PlayerController::class, 'index'])->name('show');
});

Route::prefix('/user/{user:username}')->name('users.')->group(function () {
    Route::get('/', [UserProfileController::class, 'show'])->name('show');
    Route::get('/news', [UserProfileController::class, 'news'])->name('news');
});

Route::get('/match/{id}', [MatchController::class, 'index'])->name('match.show');

Route::middleware(['throttle:60,1'])->prefix('/widget')->name('widget.')->group(function () {
    Route::get('/', [WidgetController::class, 'index'])->name('index');
    Route::get('/head-to-head', [WidgetController::class, 'headToHead'])->name('head-to-head');
    Route::get('/heatmap', [WidgetController::class, 'heatmap'])->name('heatmap');
    Route::get('/heatmap/preview', [WidgetController::class, 'heatmapPreview'])->name('heatmap.preview');
});

Route::get('/search', [SearchController::class, 'index'])->name('search.results');

Route::prefix('/news')->name('news.')->group(function () {
    Route::get('/{slug}', [NewsController::class, 'show'])->name('show');
    Route::get('/author/{slug}', [NewsController::class, 'author'])->name('author');
    Route::get('/publisher/{slug}', [NewsController::class, 'publisher'])->name('publisher');
});

Route::prefix('/forum')->name('forum.')->group(function () {
    Route::get('/', [ForumController::class, 'index'])->name('index');
    Route::get('/general', [ForumController::class, 'generalIndex'])->name('general.index');
    Route::get('/general/create', [ForumController::class, 'generalCreate'])->middleware('auth')->name('general.create');
    Route::post('/general', [ForumController::class, 'generalStore'])->middleware('auth')->name('general.store');
    Route::get('/threads/{thread}', [ForumController::class, 'show'])->name('threads.show');
});

Route::middleware(['throttle:30,1'])->group(function () {
    Route::get('/api-keys/reveal/{token}', [ApiKeyRevealController::class, 'show'])->name('api-keys.reveal');
    Route::post('/api-keys/reveal/{token}', [ApiKeyRevealController::class, 'reveal'])->name('api-keys.reveal.confirm');
});

Route::post('/preferences/theme', [ThemePreferenceController::class, 'update'])->name('preferences.theme.update');

Route::get('lang/{locale}', function ($locale) {
    $supportedLocales = array_keys(Config::get('locales.supported', []));

    if (in_array($locale, $supportedLocales)) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('lang.switch');
