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
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\Flysystem\Filesystem;
use PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNAdapter;
use PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNClient;

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

        $this->configureDefaults();
        $this->configureBunnyStorage();
        Paginator::useTailwind();

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
