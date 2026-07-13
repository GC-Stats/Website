<?php

/**
 * GC-Stats — Import all teams
 *
 * Artisan command that iterates over every inactive team with a known VLR ID
 * and re-imports their data by delegating to the import:team command.
 * Usage: php artisan import:all:teams
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Sleep;

class ImportAllTeams extends Command
{
    protected $signature = 'import:all:teams';

    protected $description = 'Import every teams';

    public function handle()
    {
        $this->info('Starting importation...');

        Team::select('vlr_id')
            ->whereNotNull('vlr_id')
            ->where('is_active', false)
            ->lazy()
            ->each(function ($team) {
                $this->info("Importing Team : {$team->vlr_id}");

                try {
                    Artisan::call('import:team', [
                        'team_id' => $team->vlr_id,
                    ], $this->getOutput());
                } catch (\Throwable $e) {
                    $this->error("Error for {$team->vlr_id}: ".$e->getMessage());
                }

                Sleep::for(1)->second();
            });

        $this->info('Done');
    }
}
