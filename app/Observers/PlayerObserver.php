<?php

/**
 * GC-Stats — Player model observer
 *
 * Purges CDN cache and flushes related cache tags for a player and their
 * teams whenever the player's data is saved or updated.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\Player;
use App\Services\BunnyCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PlayerObserver
{
    public function saved(Player $player): void
    {
        app(BunnyCache::class)->purgeUrls($this->playerUrls($player));

        Cache::tags(["player_{$player->id}"])->flush();

        $player->loadMissing('teams');
        foreach ($player->teams as $team) {
            Cache::tags(["team_{$team->id}"])->flush();

            app(BunnyCache::class)->purgeUrls([
                $this->url("/team/{$team->id}"),
            ]);
        }
    }

    public function updated(Player $player)
    {
        app(BunnyCache::class)->purgeUrls($this->playerUrls($player));

        Cache::tags(["player_{$player->id}"])->flush();

        foreach ($player->teams as $team) {
            Cache::tags(["team_{$team->id}"])->flush();

            app(BunnyCache::class)->purgeUrls([
                $this->url("/team/{$team->id}"),
            ]);
        }
    }

    /**
     * Every cached page URL for this player — the real route is singular
     * `/player/{id}/{slug}` (not `/players/{id}`), and the profile,
     * history, matches and stats pages are each cached separately, so all
     * four must be purged, not just the profile page.
     *
     * @return list<string>
     */
    private function playerUrls(Player $player): array
    {
        $slug = Str::routeSlug($player->handle, $player->id);

        return [
            $this->url("/player/{$player->id}/{$slug}"),
            $this->url("/player/{$player->id}/{$slug}/history"),
            $this->url("/player/{$player->id}/{$slug}/matches"),
            $this->url("/player/{$player->id}/{$slug}/stats"),
        ];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }
}
