<?php

/**
 * GC-Stats — Tournament model observer
 *
 * Purges CDN cache and flushes the tournament's cache tag (and the home
 * page cache) whenever the tournament's data is saved or deleted.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\Tournament;
use App\Services\BunnyCache;
use Illuminate\Support\Facades\Cache;

class TournamentObserver
{
    public function saved(Tournament $tournament): void
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        app(BunnyCache::class)->purgeUrls([
            "{$baseUrl}/tournament/{$tournament->id}",
            "{$baseUrl}/",
        ]);

        Cache::tags(["tournament_{$tournament->id}"])->flush();
        Cache::forget('home_page');
    }

    public function deleted(Tournament $tournament): void
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        app(BunnyCache::class)->purgeUrls([
            "{$baseUrl}/tournament/{$tournament->id}",
            "{$baseUrl}/",
        ]);

        Cache::tags(["tournament_{$tournament->id}"])->flush();
        Cache::forget('home_page');
    }
}
