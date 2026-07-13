<?php

/**
 * GC-Stats — Team model observer
 *
 * Purges CDN cache and flushes the team's cache tag whenever the team's
 * data is saved, updated or deleted.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\Team;
use App\Services\BunnyCache;
use Illuminate\Support\Facades\Cache;

class TeamObserver
{
    public function saved(Team $team): void
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/teams/{$team->id}"),
        ]);

        Cache::tags(["team_{$team->id}"])->flush();
    }

    public function updated(Team $team)
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/teams/{$team->id}"),
        ]);

        Cache::tags(["team_{$team->id}"])->flush();
    }

    public function deleted(Team $team)
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/teams/{$team->id}"),
        ]);

        Cache::tags(["team_{$team->id}"])->flush();
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }
}
