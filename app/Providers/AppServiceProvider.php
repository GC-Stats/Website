<?php

/**
 * GC-Stats — Main application service provider
 *
 * Registers application-wide singletons, wires up
 * model observers, and configures framework defaults (pagination,
 * JSON resources, date class, password rules, strict DB mode, etc.).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Providers;

use App\Models\Logo;
use App\Models\Matchs;
use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\NewsPublisher;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Observers\LogoObserver;
use App\Observers\MatchObserver;
use App\Observers\NewsObserver;
use App\Observers\PlayerObserver;
use App\Observers\TeamObserver;
use App\Observers\TournamentObserver;
use App\Support\AdminPermissions;
use App\Support\PermissionTeam;
use App\Support\Socialite\TwitterProviderWithCreatedAt;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Contracts\Factory as Socialite;
use League\Flysystem\Filesystem;
use PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNAdapter;
use PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNClient;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitch\TwitchExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::pattern('id', '[0-9]+');

        // Str::slug() returns '' for names/handles with no Latin-transliterable
        // characters (e.g. "星の光"), which breaks route generation for routes
        // with a required {slug} segment (Laravel can't tell an empty value
        // apart from an unfilled one). Fall back to the numeric id so the
        // slug segment is never empty.
        Str::macro('routeSlug', function ($value, $fallback) {
            $slug = Str::slug((string) $value);

            return $slug !== '' ? $slug : (string) $fallback;
        });

        $this->configureDefaults();
        $this->configureBunnyStorage();
        Paginator::useTailwind();

        // SetDefaultPermissionTeam (which sets spatie/laravel-permission's
        // team context to the global sentinel) only runs on the 'web'
        // middleware group. Outside a web request — artisan commands,
        // tinker, queued jobs, the scheduler — spatie's team resolver
        // defaults to null, and `WHERE team_id = NULL` never matches the
        // team_id = 0 rows every global role/permission assignment uses,
        // so hasRole()/can() silently see the user as having nothing.
        // Default console-context runs to global; anything that needs a
        // real team (TeamRoleService, DiscordRoleSyncService) already
        // switches explicitly via PermissionTeam::use() before checking.
        if ($this->app->runningInConsole()) {
            PermissionTeam::global();
        }

        JsonResource::withoutWrapping();

        Relation::morphMap([
            'team' => Team::class,
            'player' => Player::class,
            'tournament' => Tournament::class,
            'author' => NewsAuthor::class,
            'publisher' => NewsPublisher::class,
        ]);

        Team::observe(TeamObserver::class);
        Matchs::observe(MatchObserver::class);
        Player::observe(PlayerObserver::class);
        Tournament::observe(TournamentObserver::class);
        Logo::observe(LogoObserver::class);
        News::observe(NewsObserver::class);

        // Components only the /admin dashboard uses (e.g. <x-admin::modal>)
        // live under resources/views/admin/components rather than the
        // shared resources/views/components root.
        Blade::anonymousComponentPath(resource_path('views/admin/components'), 'admin');

        if (config('app.env') == 'production' || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        Event::listen(SocialiteWasCalled::class, [DiscordExtendSocialite::class, 'handle']);
        Event::listen(SocialiteWasCalled::class, [TwitchExtendSocialite::class, 'handle']);

        $this->app->make(Socialite::class)->extend('twitter', function ($app) {
            $config = $app['config']['services.twitter'];

            return new TwitterProviderWithCreatedAt(
                $app['request'],
                $config['client_id'],
                $config['client_secret'],
                $config['redirect'],
            );
        });

        $this->configureActivityLogging();

        // 'super-admin' always passes every ability check, including
        // permissions added later to App\Support\AdminPermissions — so
        // full access never drifts out of sync with whatever permissions
        // exist at any given time.
        Gate::before(fn ($user, string $ability) => $user->hasRole('super-admin') ? true : null);

        // Assigning/removing global site roles and editing the
        // role/permission matrix is more sensitive than any single admin
        // permission — kept as its own gate, super-admin only, rather than
        // an assignable permission so a role can never grant itself the
        // means to escalate to super-admin.
        Gate::define('manage-roles', fn ($user) => $user->hasRole('super-admin'));

        // Whether the admin dashboard (and its nav entry) shows up at all
        // — true for anyone holding at least one permission from the
        // catalog, so the entry point doesn't depend on a single umbrella
        // permission that may not match what a given role can actually do.
        Gate::define('access-admin', fn ($user) => $user->getAllPermissions()
            ->pluck('name')
            ->intersect(AdminPermissions::all())
            ->isNotEmpty());

        // Activity log access is split one permission per log type
        // (activity.account, activity.moderation — see AdminPermissions)
        // rather than a single umbrella permission, so a role can be
        // granted visibility into moderation actions without also seeing
        // account/login activity, or vice versa. This gate is the "can
        // reach the page at all" check; the controller itself further
        // restricts which log types are actually queried/shown to the
        // ones the user holds.
        Gate::define('activity.view', fn ($user) => collect(AdminPermissions::grouped()['activity'])
            ->contains(fn ($permission) => $user->can($permission)));
    }

    /**
     * Log every login/logout/failed-login through the framework's own auth
     * events rather than sprinkling activity() calls across every login
     * path (password, 2FA, passkey, Socialite) — this way none can be
     * missed as new auth methods get added.
     */
    protected function configureActivityLogging(): void
    {
        Event::listen(Login::class, function (Login $event) {
            activity('account')
                ->performedOn($event->user)
                ->causedBy($event->user)
                ->withProperties(['guard' => $event->guard, 'ip' => request()->ip()])
                ->log('account.login');
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                activity('account')->performedOn($event->user)->causedBy($event->user)->log('account.logout');
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            activity('moderation')
                ->withProperties([
                    'guard' => $event->guard,
                    'identifier' => $event->credentials[config('fortify.username')] ?? null,
                    'ip' => request()->ip(),
                ])
                ->log('account.login_failed');
        });
    }

    /**
     * Register the BunnyCDN storage disk driver used to publish the
     * public dataset export.
     */
    protected function configureBunnyStorage(): void
    {
        Storage::extend('bunnycdn', function ($app, $config) {
            $adapter = new BunnyCDNAdapter(
                new BunnyCDNClient(
                    $config['storage_zone'],
                    $config['api_key'],
                    $config['region']
                ),
                $config['pull_zone'] ?? ''
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
