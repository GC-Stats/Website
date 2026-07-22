<?php

/**
 * GC-Stats — Team cache clear
 *
 * Clears the cached data for a single team, or for every team when no ID
 * is given. Reuses TeamObserver::saved() by touching each team, so this
 * always stays in sync with what a normal save already invalidates.
 * Usage: php artisan cache:clear:teams {team_id?}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\CacheCommands;

use App\Models\Team;
use Illuminate\Console\Command;

class ClearTeamsCache extends Command
{
    protected $signature = 'cache:clear:teams {team_id? : Only clear this team}';

    protected $description = 'Clear the cache for one team, or every team if no ID is given';

    public function handle(): int
    {
        if ($teamId = $this->argument('team_id')) {
            $team = Team::find($teamId);

            if (! $team) {
                $this->error("Team {$teamId} not found.");

                return self::FAILURE;
            }

            $team->touch();
            $this->info("Cache cleared for team {$teamId}.");

            return self::SUCCESS;
        }

        $count = Team::count();
        $bar = $this->output->createProgressBar($count);

        Team::query()->orderBy('id')->chunk(200, function ($teams) use ($bar) {
            foreach ($teams as $team) {
                $team->touch();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Cache cleared for {$count} team(s).");

        return self::SUCCESS;
    }
}
