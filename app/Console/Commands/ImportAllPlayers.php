<?php

/**
 * GC-Stats — Import all players
 *
 * Artisan command that iterates over every player with a known VLR ID
 * and re-imports their data by delegating to the import:player command.
 * Usage: php artisan import:all:players
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Sleep;

class ImportAllPlayers extends Command
{
    protected $signature = 'import:all:players';

    protected $description = 'Import every players';

    public function handle()
    {
        $this->info('Starting importation...');

        Player::select('vlr_id')
            ->whereNotNull('vlr_id')
            ->lazy()
            ->each(function ($player) {
                $this->info("Imporing Player : {$player->vlr_id}");

                try {
                    Artisan::call('import:player', [
                        'player_id' => $player->vlr_id,
                    ]);
                } catch (\Throwable $e) {
                    $this->error("Error for {$player->vlr_id}: ".$e->getMessage());
                }

                Sleep::for(1)->second();
            });

        $this->info('Done');
    }
}
