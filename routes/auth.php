<?php

/**
 * GC-Stats — Auth & account routes
 *
 * Adds what Fortify doesn't cover (see config/fortify.php): Socialite
 * redirect/callback, provider linking, and account settings actions.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use App\Http\Controllers\Auth\AccountSettingsController;
use App\Http\Controllers\Auth\ResendVerificationController;
use App\Http\Controllers\Auth\SocialAccountController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\UserReportController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->middleware(['not-sanctioned'])
    ->name('social.callback');

// Reachable both logged out (a password-only account can't log in until
// verified — see FortifyServiceProvider::authenticateUsing) and logged in
// (e.g. an already-authenticated but unverified user landing here directly
// instead of via the verify-email notice page's own resend button).
Route::get('/email/verify/resend', [ResendVerificationController::class, 'create'])
    ->name('verification.resend');
Route::post('/email/verify/resend', [ResendVerificationController::class, 'store'])
    ->middleware(['throttle:5,1'])
    ->name('verification.resend.send');

Route::middleware(['auth'])->group(function () {
    Route::get('/settings/account', [AccountSettingsController::class, 'edit'])
        ->name('account.edit');
    Route::get('/settings/account/export', [AccountSettingsController::class, 'exportData'])
        ->name('account.export');
    Route::delete('/settings/account', [AccountSettingsController::class, 'destroyAccount'])
        ->name('account.destroy');

    Route::middleware(['not-sanctioned'])->group(function () {
        Route::delete('/settings/social/{socialAccount}', [SocialAccountController::class, 'destroy'])
            ->name('social.destroy');

        Route::put('/settings/account/password', [AccountSettingsController::class, 'setPassword'])
            ->name('account.password.update');
        Route::delete('/settings/account/password', [AccountSettingsController::class, 'destroyPassword'])
            ->name('account.password.destroy');

        Route::put('/settings/account/team', [AccountSettingsController::class, 'updateFanTeam'])
            ->name('account.team.update');

        Route::post('/users/{user}/report', [UserReportController::class, 'store'])
            ->middleware('throttle:15,60')
            ->name('users.report');
    });
});
