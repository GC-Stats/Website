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
use App\Models\Organization;
use App\Models\PhaseQualification;
use App\Models\PhaseQualificationResult;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Observers\LogoObserver;
use App\Observers\MatchObserver;
use App\Observers\NewsObserver;
use App\Observers\PhaseQualificationObserver;
use App\Observers\PhaseQualificationResultObserver;
use App\Observers\PlayerObserver;
use App\Observers\TeamObserver;
use App\Observers\TournamentObserver;
use App\Support\AdminPermissions;
use App\Support\OrganizationPermissions;
use App\Support\OrganizationScope;
use App\Support\PermissionTeam;
use App\Support\Socialite\TwitterProviderWithCreatedAt;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\ParallelTesting;
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
use Spatie\Permission\PermissionRegistrar;

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

        Str::macro('routeSlug', function ($value, $fallback) {
            $slug = Str::slug((string) $value);

            return $slug !== '' ? $slug : (string) $fallback;
        });

        $this->configureDefaults();
        $this->configureBunnyStorage();
        $this->ensureCacheStoreSupportsTagging();
        Paginator::useTailwind();

        if ($this->app->runningUnitTests() && ($token = ParallelTesting::token())) {
            app(PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache.'.$token;
        }

        if ($this->app->runningInConsole()) {
            PermissionTeam::global();
        }

        JsonResource::withoutWrapping();

        Relation::morphMap([
            'team' => Team::class,
            'player' => Player::class,
            'tournament' => Tournament::class,
            'match' => Matchs::class,
            'author' => NewsAuthor::class,
            'staff' => Staff::class,
            'organization' => Organization::class,
        ]);

        Team::observe(TeamObserver::class);
        Matchs::observe(MatchObserver::class);
        Player::observe(PlayerObserver::class);
        Tournament::observe(TournamentObserver::class);
        Logo::observe(LogoObserver::class);
        News::observe(NewsObserver::class);
        PhaseQualification::observe(PhaseQualificationObserver::class);
        PhaseQualificationResult::observe(PhaseQualificationResultObserver::class);

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

        Gate::before(fn ($user, string $ability) => $user->isSuperAdmin() ? true : null);

        Gate::before(fn ($user, string $ability) => str_starts_with($ability, 'organization.')
            ? ($user->hasPermissionTo($ability, OrganizationPermissions::GUARD) ?: null)
            : null);

        Gate::define('manage-roles', fn ($user) => $user->isSuperAdmin());

        Gate::define('access-admin', fn ($user) => $user->getAllPermissions()
            ->pluck('name')
            ->intersect(AdminPermissions::all())
            ->isNotEmpty()
            || OrganizationScope::organizationIdsForUser($user->id)->isNotEmpty());

        Gate::define('access-developers', fn (User $user) => $user->apiKeys()
            ->where('is_active', true)
            ->exists());

        Gate::define('activity.view', fn ($user) => collect(AdminPermissions::grouped()['activity'])
            ->contains(fn ($permission) => $user->can($permission)));

        Gate::define('news.nav.articles', fn ($user) => $user->can('news.view')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.news.view')->isNotEmpty());

        Gate::define('news.nav.authors', fn ($user) => $user->can('news.authors.view')
            || $user->newsAuthor()->exists()
            || OrganizationScope::organizationIdsForUser($user->id)->isNotEmpty());

        Gate::define('news.nav.media', fn ($user) => $user->can('news.media.view')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.media.view')->isNotEmpty());

        Gate::define('news.action.create', fn ($user) => $user->can('news.create')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.news.edit')->isNotEmpty());

        Gate::define('news.media.action.upload', fn ($user) => $user->can('news.media.upload')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.media.upload')->isNotEmpty());

        Gate::define('streams.nav.channels', fn ($user) => $user->can('streams.channels.view')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.streams.view')->isNotEmpty());

        Gate::define('streams.action.create', fn ($user) => $user->can('streams.channels.create')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.streams.edit')->isNotEmpty());

        Gate::define('streams.nav.matches', fn ($user) => $user->can('streams.matches.link')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.streams.link')->isNotEmpty());

        Gate::define('vods.nav.matches', fn ($user) => $user->can('vods.matches.link')
            || OrganizationScope::organizationIdsWithPermission($user->id, 'organization.vods.link')->isNotEmpty());
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
     * Cache::tags() is relied on throughout the app (see the Observers and
     * *MergeService/RosterService classes) as the only invalidation
     * mechanism for per-entity cached data — it silently throws
     * BadMethodCallException on drivers that don't support tagging
     * (database, file). Fail loudly at boot instead of letting a
     * misconfigured CACHE_STORE surface as random 500s across the site.
     */
    protected function ensureCacheStoreSupportsTagging(): void
    {
        // composer's post-autoload-dump runs `artisan package:discover` (and
        // `vendor:publish` on post-update-cmd) before a .env exists — e.g. on
        // a fresh `composer install` in CI — which boots every provider with
        // whatever CACHE_STORE default happens to apply. Neither command
        // touches Cache::tags(), so don't fail the install over it.
        if ($this->app->runningInConsole() && in_array($_SERVER['argv'][1] ?? null, ['package:discover', 'vendor:publish'], true)) {
            return;
        }

        if (! Cache::getStore() instanceof TaggableStore) {
            throw new \RuntimeException(sprintf(
                'CACHE_STORE="%s" does not support Cache::tags(), which this application relies on throughout for cache invalidation. Use redis, memcached, or array.',
                config('cache.default'),
            ));
        }
    }

    /**
     * Register the BunnyCDN storage disk driver, used by the "bunny" disk
     * (dataset export) and, when FILESYSTEM_DISK_PUBLIC=bunnycdn, the
     * "public" disk (logos, emotes, news images).
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
