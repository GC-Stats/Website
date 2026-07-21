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
use App\Support\PublisherPermissions;
use App\Support\PublisherScope;
use App\Support\Socialite\TwitterProviderWithCreatedAt;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
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
            'author' => NewsAuthor::class,
            'publisher' => NewsPublisher::class,
        ]);

        Team::observe(TeamObserver::class);
        Matchs::observe(MatchObserver::class);
        Player::observe(PlayerObserver::class);
        Tournament::observe(TournamentObserver::class);
        Logo::observe(LogoObserver::class);
        News::observe(NewsObserver::class);

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

        Gate::before(fn ($user, string $ability) => str_starts_with($ability, 'publisher.')
            ? ($user->hasPermissionTo($ability, PublisherPermissions::GUARD) ?: null)
            : null);

        Gate::define('manage-roles', fn ($user) => $user->isSuperAdmin());

        Gate::define('access-admin', fn ($user) => $user->getAllPermissions()
            ->pluck('name')
            ->intersect(AdminPermissions::all())
            ->isNotEmpty()
            || $user->newsAuthor()->exists()
            || PublisherScope::publisherIdsForUser($user->id)->isNotEmpty());

        Gate::define('activity.view', fn ($user) => collect(AdminPermissions::grouped()['activity'])
            ->contains(fn ($permission) => $user->can($permission)));

        Gate::define('news.nav.articles', fn ($user) => $user->can('news.view')
            || PublisherScope::publisherIdsWithPermission($user->id, 'publisher.news.view')->isNotEmpty());

        Gate::define('news.nav.publishers', fn ($user) => $user->can('news.publishers.view')
            || PublisherScope::publisherIdsForUser($user->id)->isNotEmpty());

        Gate::define('news.nav.authors', fn ($user) => $user->can('news.authors.view')
            || $user->newsAuthor()->exists()
            || PublisherScope::publisherIdsForUser($user->id)->isNotEmpty());

        Gate::define('news.nav.media', fn ($user) => $user->can('news.media.view')
            || PublisherScope::publisherIdsWithPermission($user->id, 'publisher.media.view')->isNotEmpty());

        Gate::define('news.action.create', fn ($user) => $user->can('news.create')
            || PublisherScope::publisherIdsWithPermission($user->id, 'publisher.news.edit')->isNotEmpty());

        Gate::define('news.media.action.upload', fn ($user) => $user->can('news.media.upload')
            || PublisherScope::publisherIdsWithPermission($user->id, 'publisher.media.upload')->isNotEmpty());
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
