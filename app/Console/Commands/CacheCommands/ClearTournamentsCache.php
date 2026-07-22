<?php

/**
 * GC-Stats — Tournament cache clear
 *
 * Clears the cached data for a single tournament, or for every tournament
 * when no ID is given. Reuses TournamentObserver::saved() by touching each
 * tournament, so this always stays in sync with what a normal save already
 * invalidates.
 * Usage: php artisan cache:clear:tournaments {tournament_id?}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\CacheCommands;

use App\Models\Tournament;
use Illuminate\Console\Command;

class ClearTournamentsCache extends Command
{
    protected $signature = 'cache:clear:tournaments {tournament_id? : Only clear this tournament}';

    protected $description = 'Clear the cache for one tournament, or every tournament if no ID is given';

    public function handle(): int
    {
        if ($tournamentId = $this->argument('tournament_id')) {
            $tournament = Tournament::find($tournamentId);

            if (! $tournament) {
                $this->error("Tournament {$tournamentId} not found.");

                return self::FAILURE;
            }

            $tournament->touch();
            $this->info("Cache cleared for tournament {$tournamentId}.");

            return self::SUCCESS;
        }

        $count = Tournament::count();
        $bar = $this->output->createProgressBar($count);

        Tournament::query()->orderBy('id')->chunk(200, function ($tournaments) use ($bar) {
            foreach ($tournaments as $tournament) {
                $tournament->touch();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Cache cleared for {$count} tournament(s).");

        return self::SUCCESS;
    }
}
