<?php

use App\Http\Middleware\EnsureAccountIsNotSanctioned;
use App\Http\Middleware\EnsureNotSanctionedForTeam;
use App\Http\Middleware\InternalServiceAuth;
use App\Http\Middleware\LogPageView;
use App\Http\Middleware\SetDefaultPermissionTeam;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPublisherPermissionContext;
use App\Http\Middleware\SetTeamPermissionContext;
use App\Http\Middleware\StaticPageCache;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('app:sync-page-views')->hourly();
        $schedule->command('sitemap:generate')->daily();
        $schedule->command('data:export-public')->dailyAt('03:00');
        // Matches the default reveal TTL (config('api_keys.reveal_ttl_minutes'))
        // so expired-but-still-encrypted key blobs don't linger far past
        // their own expiry.
        $schedule->command('app:prune-api-key-reveals')->everyFifteenMinutes();
        $schedule->command('discord:sync-roles')->everyFifteenMinutes();
        $schedule->command('matches:activate-live')->everyMinute();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(LogPageView::class);
        $middleware->web(append: [
            SetLocale::class,
            SetDefaultPermissionTeam::class,
        ]);

        $middleware->alias([
            'internal.service' => InternalServiceAuth::class,
            'static.cache' => StaticPageCache::class,
            'not-sanctioned' => EnsureAccountIsNotSanctioned::class,
            'not-sanctioned.team' => EnsureNotSanctionedForTeam::class,
            'team.permission-context' => SetTeamPermissionContext::class,
            'publisher.permission-context' => SetPublisherPermissionContext::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
