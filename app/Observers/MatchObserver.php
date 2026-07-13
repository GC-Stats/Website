<?php

/**
 * GC-Stats — Match model observer
 *
 * Purges CDN cache and flushes related cache tags (match, tournament, teams,
 * players) whenever a match is saved or deleted, keeping public pages fresh.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\Matchs;
use App\Services\BunnyCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MatchObserver
{
    public function updated(Matchs $match)
    {
        if ($match->wasChanged(['team_a_id', 'team_b_id'])) {
            $this->clearGameMapPlayerData($match);
        }
    }

    public function saved(Matchs $match)
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        app(BunnyCache::class)->purgeUrls([
            "{$baseUrl}/match/{$match->id}",
            "{$baseUrl}/tournament/{$match->tournament_id}",
            "{$baseUrl}/",
        ]);

        if ($match->status === 'finished' && ! empty($match->stats)) {
            if (is_iterable($match->stats)) {
                foreach ($match->stats as $stat) {
                    if (isset($stat->player_id)) {
                        Cache::tags(["player_{$stat->player_id}"])->flush();
                    }
                }
            }
        }

        Cache::tags(["team_{$match->team_a_id}"])->flush();
        Cache::tags(["team_{$match->team_b_id}"])->flush();
        Cache::tags(["tournament_{$match->tournament_id}"])->flush();
        Cache::forget('home_page');
        Cache::forget("match_{$match->id}");

    }

    public function deleted(Matchs $match)
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        app(BunnyCache::class)->purgeUrls([
            "{$baseUrl}/match/{$match->id}",
            "{$baseUrl}/tournament/{$match->tournament_id}",
            "{$baseUrl}/",
        ]);

        Cache::tags(["team_{$match->team_a_id}"])->flush();
        Cache::tags(["team_{$match->team_b_id}"])->flush();
        Cache::tags(["tournament_{$match->tournament_id}"])->flush();
        Cache::forget('home_page');
        Cache::forget("match_{$match->id}");
    }

    /**
     * Clear player-linked stats (rounds, player stats, advanced stats) from
     * every game map of the match, since they still reference players from
     * the previous team roster once a team is changed.
     */
    private function clearGameMapPlayerData(Matchs $match): void
    {
        DB::transaction(function () use ($match) {
            foreach ($match->game_maps as $gameMap) {
                $gameMap->rounds()->delete();
                $gameMap->playerStats()->delete();
                $gameMap->advancedStats()->delete();

                $gameMap->update([
                    'api_match_id' => null,
                    'team_a_score' => null,
                    'team_b_score' => null,
                    'is_completed' => false,
                ]);
            }
        });
    }
}
