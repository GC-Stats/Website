<?php

/**
 * GC-Stats — Match cache clear
 *
 * Clears the cached data for a single match (its own cache entry, its two
 * teams, its tournament, and — if finished — its players' stats), or for
 * every match when no ID is given. Reuses MatchObserver::saved() by
 * touching each match, so this always stays in sync with what a normal
 * save already invalidates.
 * Usage: php artisan cache:clear:matches {match_id?}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\CacheCommands;

use App\Models\Matchs;
use Illuminate\Console\Command;

class ClearMatchesCache extends Command
{
    protected $signature = 'cache:clear:matches {match_id? : Only clear this match (and everything it touches)}';

    protected $description = 'Clear the cache for one match, or every match if no ID is given';

    public function handle(): int
    {
        if ($matchId = $this->argument('match_id')) {
            $match = Matchs::find($matchId);

            if (! $match) {
                $this->error("Match {$matchId} not found.");

                return self::FAILURE;
            }

            $match->touch();
            $this->info("Cache cleared for match {$matchId} (its teams, tournament and player stats included).");

            return self::SUCCESS;
        }

        $count = Matchs::count();
        $bar = $this->output->createProgressBar($count);

        Matchs::query()->orderBy('id')->chunk(200, function ($matches) use ($bar) {
            foreach ($matches as $match) {
                $match->touch();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Cache cleared for {$count} match(es).");

        return self::SUCCESS;
    }
}
