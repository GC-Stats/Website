<?php

/**
 * GC-Stats — Import all team logos
 *
 * Artisan command that iterates over every team with a known VLR ID and
 * imports its logo by delegating to the import:team:logo command, which
 * skips teams that already have a current logo.
 * Usage: php artisan import:all:teams:logo
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ImportCommands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Sleep;

class ImportAllTeamLogos extends Command
{
    protected $signature = 'import:all:teams:logo';

    protected $description = 'Import logos for every team';

    public function handle()
    {
        $this->info('Starting logo importation...');

        Team::select('vlr_id')
            ->whereNotNull('vlr_id')
            ->lazy()
            ->each(function ($team) {
                $this->info("Importing Logo : {$team->vlr_id}");

                try {
                    Artisan::call('import:team:logo', [
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
