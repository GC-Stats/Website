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
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\TransparencyController;
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

Route::get('/player/{id}/history', [PlayerController::class, 'history']);
Route::get('/player/{id}/{slug}/history', [PlayerController::class, 'history'])->name('players.history');
Route::get('/player/{id}/matches', [PlayerController::class, 'matches']);
Route::get('/player/{id}/{slug}/matches', [PlayerController::class, 'matches'])->name('players.matches');
Route::get('/player/{id}/stats', [PlayerController::class, 'stats']);
Route::get('/player/{id}/{slug}/stats', [PlayerController::class, 'stats'])->name('players.stats');
Route::get('/player/{id}/{slug?}', [PlayerController::class, 'index'])->name('players.show');

Route::get('/team/{id}/history', [TeamController::class, 'history']);
Route::get('/team/{id}/{slug}/history', [TeamController::class, 'history'])->name('teams.history');
Route::get('/team/{id}/matches', [TeamController::class, 'matches']);
Route::get('/team/{id}/{slug}/matches', [TeamController::class, 'matches'])->name('teams.matches');
Route::get('/team/{id}/maps', [TeamController::class, 'maps']);
Route::get('/team/{id}/{slug}/maps', [TeamController::class, 'maps'])->name('teams.maps');
Route::get('/team/{id}/{slug?}', [TeamController::class, 'index'])->name('teams.show');

Route::get('/user/{user:username}', [UserProfileController::class, 'show'])->name('users.show');
Route::get('/user/{user:username}/news', [UserProfileController::class, 'news'])->name('users.news');

Route::get('/match/{id}', [MatchController::class, 'index'])->name('match.show');

Route::get('/search', [SearchController::class, 'index'])->name('search.results');

Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');
Route::get('/news/author/{slug}', [NewsController::class, 'author'])->name('news.author');
Route::get('/news/publisher/{slug}', [NewsController::class, 'publisher'])->name('news.publisher');

Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
Route::get('/tournaments/{tournament}/matches', [TournamentController::class, 'matches']);
Route::get('/tournaments/{tournament}/{slug}/matches', [TournamentController::class, 'matches'])->name('tournaments.matches');
Route::get('/tournaments/{id}/stats', [TournamentController::class, 'stats']);
Route::get('/tournaments/{id}/{slug}/stats', [TournamentController::class, 'stats'])->name('tournaments.stats');
Route::get('/tournaments/{id}/maps', [TournamentController::class, 'maps']);
Route::get('/tournaments/{id}/{slug}/maps', [TournamentController::class, 'maps'])->name('tournaments.maps');
Route::get('/tournaments/{tournament}/{slug?}', [TournamentController::class, 'show'])->name('tournaments.show');

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
