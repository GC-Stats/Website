<?php

/**
 * GC-Stats — Player cache clear
 *
 * Clears the cached data for a single player (and their teams), or for
 * every player when no ID is given. Reuses PlayerObserver::saved() by
 * touching each player, so this always stays in sync with what a normal
 * save already invalidates.
 * Usage: php artisan cache:clear:players {player_id?}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\CacheCommands;

use App\Models\Player;
use Illuminate\Console\Command;

class ClearPlayersCache extends Command
{
    protected $signature = 'cache:clear:players {player_id? : Only clear this player (and their teams)}';

    protected $description = 'Clear the cache for one player, or every player if no ID is given';

    public function handle(): int
    {
        if ($playerId = $this->argument('player_id')) {
            $player = Player::find($playerId);

            if (! $player) {
                $this->error("Player {$playerId} not found.");

                return self::FAILURE;
            }

            $player->touch();
            $this->info("Cache cleared for player {$playerId} (their teams included).");

            return self::SUCCESS;
        }

        $count = Player::count();
        $bar = $this->output->createProgressBar($count);

        Player::query()->orderBy('id')->chunk(200, function ($players) use ($bar) {
            foreach ($players as $player) {
                $player->touch();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Cache cleared for {$count} player(s).");

        return self::SUCCESS;
    }
}
