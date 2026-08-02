<?php

use App\Http\Middleware\EnsureAccountIsNotSanctioned;
use App\Http\Middleware\EnsureDataExplorerIsEnabled;
use App\Http\Middleware\InternalServiceAuth;
use App\Http\Middleware\LogPageView;
use App\Http\Middleware\SetDefaultPermissionTeam;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPublisherPermissionContext;
use App\Http\Middleware\StaticPageCache;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        $schedule->command('app:prune-api-key-reveals')->everyFifteenMinutes();
        $schedule->command('discord:sync-roles')->everyFifteenMinutes();

        $schedule->command('app:reset-data-explorer-usage')->monthlyOn(1, '00:05');
        $schedule->command('app:prune-data-explorer-error-logs')->daily();

        $schedule->command('matches:activate-live')->everyMinute();
        $schedule->command('tournaments:activate-live')->daily();
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
            'data-explorer.enabled' => EnsureDataExplorerIsEnabled::class,
            'publisher.permission-context' => SetPublisherPermissionContext::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();

                if ($request->is('admin*')) {
                    if (view()->exists("admin.errors.{$status}")) {
                        return response()->view("admin.errors.{$status}", ['exception' => $e], $status);
                    }

                    // Vérifie aussi si la vue par défaut existe bien
                    if (view()->exists('admin.errors.default')) {
                        return response()->view('admin.errors.default', ['exception' => $e], $status);
                    }
                }

                if ($request->is('developers') || $request->is('developers/*')) {
                    if (view()->exists("developers.errors.{$status}")) {
                        return response()->view("developers.errors.{$status}", ['exception' => $e], $status);
                    }
                }
            }
        });
    })->create();
