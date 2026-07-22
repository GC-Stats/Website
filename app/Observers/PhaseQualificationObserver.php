<?php

/**
 * GC-Stats — Phase qualification rule observer
 *
 * Purges CDN cache and flushes the owning tournament's cache tag whenever a
 * qualification rule is saved or deleted — without this, the public
 * tournament page (cached per tournament_{id}, see TournamentController::show())
 * keeps serving a stale version with no qualification badges/leaderboard
 * rows until something else happens to bust that tag.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\PhaseQualification;
use App\Services\BunnyCache;
use App\Services\PhaseQualificationResolver;
use Illuminate\Support\Facades\Cache;

class PhaseQualificationObserver
{
    public function saved(PhaseQualification $qualification): void
    {
        $this->flush($qualification);

        $resolver = app(PhaseQualificationResolver::class);

        if ($qualification->source_match_id) {
            $resolver->resolveForMatch($qualification->source_match_id);
        } else {
            $resolver->resolveForPhase($qualification->source_phase_id);
        }
    }

    public function deleted(PhaseQualification $qualification): void
    {
        $this->flush($qualification);
        // Its PhaseQualificationResult rows already cascade-deleted at the DB level.
    }

    /**
     * A match-outcome rule's source_phase_id already mirrors its match's
     * tournament (see PhaseQualificationController::storeForMatch()), so
     * loading sourcePhase covers both rank-based and match-based rules.
     */
    private function flush(PhaseQualification $qualification): void
    {
        $tournamentId = $qualification->sourcePhase?->tournament_id;

        if (! $tournamentId) {
            return;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');

        app(BunnyCache::class)->purgeUrls([
            "{$baseUrl}/tournament/{$tournamentId}",
        ]);

        Cache::tags(["tournament_{$tournamentId}"])->flush();
    }
}
