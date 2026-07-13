<?php

/**
 * GC-Stats — Logo model observer
 *
 * Purges CDN cache and flushes cache tags for the associated entity
 * (team, player or tournament) whenever a logo is saved or deleted.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\Logo;
use App\Services\BunnyCache;
use Illuminate\Support\Facades\Cache;

class LogoObserver
{
    public function saved(Logo $logo): void
    {
        $this->invalidate($logo);
    }

    public function deleted(Logo $logo): void
    {
        $this->invalidate($logo);
    }

    private function invalidate(Logo $logo): void
    {
        $entity = $logo->entity;

        if (! $entity) {
            return;
        }

        match ($logo->entity_type) {
            'team' => $this->invalidateTeam($entity),
            'player' => $this->invalidatePlayer($entity),
            'tournament' => $this->invalidateTournament($entity),
            default => null,
        };
    }

    private function invalidateTeam($team): void
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/teams/{$team->id}"),
        ]);

        Cache::tags(["team_{$team->id}"])->flush();
    }

    private function invalidatePlayer($player): void
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/players/{$player->id}"),
        ]);

        Cache::tags(["player_{$player->id}"])->flush();
    }

    private function invalidateTournament($tournament): void
    {
        app(BunnyCache::class)->purgeUrls([
            $this->url("/tournament/{$tournament->id}"),
            $this->url('/'),
        ]);

        Cache::tags(["tournament_{$tournament->id}"])->flush();
        Cache::forget('home_page');
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }
}
