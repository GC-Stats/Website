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
use App\Http\Controllers\Auth\NotificationController;
use App\Http\Controllers\Auth\PlayerChangeRequestController;
use App\Http\Controllers\Auth\ResendVerificationController;
use App\Http\Controllers\Auth\SocialAccountController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TeamChangeRequestController;
use App\Http\Controllers\Auth\UserChangeRequestController;
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

    // Always reachable, even for a sanctioned account — tracking one's own
    // past requests is read access, not a new action to gate.
    Route::get('/settings/change-requests', [UserChangeRequestController::class, 'index'])
        ->name('account.change-requests.index');
    Route::get('/settings/change-requests/{changeRequest}', [UserChangeRequestController::class, 'show'])
        ->name('account.change-requests.show');

    // Always reachable, even for a sanctioned account
    Route::get('/settings/notifications', [NotificationController::class, 'index'])
        ->name('account.notifications.index');
    Route::post('/settings/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('account.notifications.read-all');
    Route::put('/settings/notifications/email-preferences', [NotificationController::class, 'updateEmailPreferences'])
        ->name('account.notifications.email-preferences.update');
    Route::get('/settings/notifications/{notification}/open', [NotificationController::class, 'open'])
        ->name('account.notifications.open');

    Route::middleware(['not-sanctioned'])->group(function () {
        Route::delete('/settings/social/{socialAccount}', [SocialAccountController::class, 'destroy'])
            ->name('social.destroy');

        Route::put('/settings/account/password', [AccountSettingsController::class, 'setPassword'])
            ->name('account.password.update');
        Route::delete('/settings/account/password', [AccountSettingsController::class, 'destroyPassword'])
            ->name('account.password.destroy');

        Route::put('/settings/account/team', [AccountSettingsController::class, 'updateFanTeam'])
            ->name('account.team.update');

        Route::put('/settings/account/bio', [AccountSettingsController::class, 'updateBio'])
            ->name('account.bio.update');

        Route::post('/settings/change-requests/{changeRequest}/messages', [UserChangeRequestController::class, 'storeMessage'])
            ->middleware('throttle:30,60')
            ->name('account.change-requests.messages.store');

        Route::post('/users/{user}/report', [UserReportController::class, 'store'])
            ->middleware('throttle:15,60')
            ->name('users.report');

        Route::get('/players/{player}/change-requests/create', [PlayerChangeRequestController::class, 'create'])
            ->name('players.change-requests.create');
        Route::post('/players/{player}/change-requests', [PlayerChangeRequestController::class, 'store'])
            ->middleware('throttle:15,60')
            ->name('players.change-requests.store');

        Route::get('/teams/{team}/change-requests/create', [TeamChangeRequestController::class, 'create'])
            ->name('teams.change-requests.create');
        Route::post('/teams/{team}/change-requests', [TeamChangeRequestController::class, 'store'])
            ->middleware('throttle:15,60')
            ->name('teams.change-requests.store');
    });
});
