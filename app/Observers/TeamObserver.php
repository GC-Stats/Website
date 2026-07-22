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
        app(BunnyCache::class)->purgeUrls($this->teamUrls($team));

        Cache::tags(["team_{$team->id}"])->flush();
    }

    public function updated(Team $team)
    {
        app(BunnyCache::class)->purgeUrls($this->teamUrls($team));

        Cache::tags(["team_{$team->id}"])->flush();
    }

    public function deleted(Team $team)
    {
        app(BunnyCache::class)->purgeUrls($this->teamUrls($team));

        Cache::tags(["team_{$team->id}"])->flush();
    }

    /**
     * Every cached page URL for this team — the real route is singular
     * `/team/{id}/{slug}` (not `/teams/{id}`), and the profile, history,
     * matches and maps pages are each cached separately, so all four must
     * be purged, not just the profile page.
     *
     * @return list<string>
     */
    private function teamUrls(Team $team): array
    {
        $slug = $team->routeSlug();

        return [
            $this->url("/team/{$team->id}/{$slug}"),
            $this->url("/team/{$team->id}/{$slug}/history"),
            $this->url("/team/{$team->id}/{$slug}/matches"),
            $this->url("/team/{$team->id}/{$slug}/maps"),
        ];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }
}
