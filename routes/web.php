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

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ApiKeyRevealController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TransparencyController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/team.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware(['static.cache:2592000'])->group(function () {
    Route::get('/terms', function () {
        return view('legal.terms');
    })->name('terms');

    Route::get('/legal', function () {
        return view('legal.legal');
    })->name('legal');

    Route::get('/privacy', function () {
        return view('legal.privacy');
    })->name('privacy');

    Route::get('/data', function () {
        return view('data');
    })->name('data');

    Route::get('/takedown', function () {
        return view('legal.takedown');
    })->name('takedown');

    Route::get('/help/edit_page', function () {
        return view('help/edit_page');
    })->name('help.edit_page');

    Route::get('/help/add_tournament', function () {
        return view('help/add_tournament');
    })->name('help.add_tournament');

    Route::get('/developers', function () {
        return view('developers');
    })->name('developers');
});

Route::middleware(['static.cache:300'])->group(function () {
    Route::get('/about', [AboutController::class, 'index'])->name('about');
    Route::get('/transparency', [TransparencyController::class, 'index'])->name('transparency');
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
});

Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
Route::prefix('/tournaments/{tournament}/{slug}')->name('tournaments.')->group(function () {
    Route::get('/', [TournamentController::class, 'show'])->name('show');
    Route::get('/matches', [TournamentController::class, 'matches'])->name('matches');
    Route::get('/stats', [TournamentController::class, 'stats'])->name('stats');
    Route::get('/maps', [TournamentController::class, 'maps'])->name('maps');
});

Route::prefix('/team/{team}/{slug}')->name('teams.')->group(function () {
    Route::get('//history', [TeamController::class, 'history'])->name('history');
    Route::get('/matches', [TeamController::class, 'matches'])->name('matches');
    Route::get('/maps', [TeamController::class, 'maps'])->name('maps');
    Route::get('/', [TeamController::class, 'index'])->name('show');
});

Route::prefix('/user/{user:username}')->name('users.')->group(function () {
    Route::get('/', [UserProfileController::class, 'show'])->name('show');
    Route::get('/news', [UserProfileController::class, 'news'])->name('news');
});

Route::get('/match/{id}', [MatchController::class, 'index'])->name('match.show');

Route::get('/search', [SearchController::class, 'index'])->name('search.results');

Route::prefix('/news')->name('news.')->group(function () {
    Route::get('/{slug}', [NewsController::class, 'show'])->name('show');
    Route::get('/author/{slug}', [NewsController::class, 'author'])->name('author');
    Route::get('/publisher/{slug}', [NewsController::class, 'publisher'])->name('publisher');
});

Route::middleware(['throttle:30,1'])->group(function () {
    Route::get('/api-keys/reveal/{token}', [ApiKeyRevealController::class, 'show'])->name('api-keys.reveal');
    Route::post('/api-keys/reveal/{token}', [ApiKeyRevealController::class, 'reveal'])->name('api-keys.reveal.confirm');
});

Route::get('lang/{locale}', function ($locale) {
    $supportedLocales = array_keys(Config::get('locales.supported', []));

    if (in_array($locale, $supportedLocales)) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('lang.switch');
