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

class PlayerObserver
{
    public function saved(Player $player): void
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/players/{$player->id}"),
        ]);

        Cache::tags(["player_{$player->id}"])->flush();

        $player->loadMissing('teams');
        foreach ($player->teams as $team) {
            Cache::tags(["team_{$team->id}"])->flush();

            app(BunnyCache::class)->purgeUrls([
                $this->url("/teams/{$team->id}"),
            ]);
        }
    }

    public function updated(Player $player)
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/players/{$player->id}"),
        ]);

        Cache::tags(["player_{$player->id}"])->flush();

        foreach ($player->teams as $team) {
            Cache::tags(["team_{$team->id}"])->flush();

            app(BunnyCache::class)->purgeUrls([
                $this->url("/teams/{$team->id}"),
            ]);
        }
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }
}
