<?php

/**
 * GC-Stats — Phase qualification result observer
 *
 * Flushes the owning team/player's cache tag whenever a resolved
 * qualification result is saved or deleted, so the achievements panel on
 * their profile page (see App\Support\Achievements, cached alongside the
 * rest of team_page_{id}/player_page_{id}) doesn't keep serving a stale
 * version once PhaseQualificationResolver adds/removes a result.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\PhaseQualificationResult;
use App\Services\BunnyCache;
use Illuminate\Support\Facades\Cache;

class PhaseQualificationResultObserver
{
    public function saved(PhaseQualificationResult $result): void
    {
        $this->flush($result);
    }

    public function deleted(PhaseQualificationResult $result): void
    {
        $this->flush($result);
    }

    private function flush(PhaseQualificationResult $result): void
    {
        $path = match ($result->entity_type) {
            'team' => "/teams/{$result->entity_id}",
            'player' => "/players/{$result->entity_id}",
            default => null,
        };

        if (! $path) {
            return;
        }

        app(BunnyCache::class)->purgeUrls([
            rtrim((string) config('app.url'), '/').$path,
        ]);

        Cache::tags(["{$result->entity_type}_{$result->entity_id}"])->flush();
    }
}
