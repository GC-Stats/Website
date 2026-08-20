<?php

/**
 * GC-Stats — Sitemap generator
 *
 * Artisan command that builds the public sitemap.xml file, listing static
 * pages as well as tournaments, teams, players and matches for SEO.
 * Usage: php artisan sitemap:generate
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Models\GameMap;
use App\Services\RiotRelayClient;
use Illuminate\Console\Command;

class RenewAllMatches extends Command
{
    protected $signature = 'val:matches:renew';

    protected $description = 'Renew every matches cache';

    public function handle(RiotRelayClient $relay)
    {
        $query = GameMap::query()
            ->whereNotNull('api_match_id');

        $maps = $query->get();

        $this->info("Found {$maps->count()} map(s) to renew.");

        foreach ($maps as $gameMap) {
            usleep((int) 500);

            $region = config('regions.riot_api.'.$gameMap->match->tournament->region);

            $result = $relay->renewMatch($region, $gameMap->api_match_id);

            if (! $result->successful) {
                $result = $relay->renewMatch('esports', $gameMap->api_match_id);

                if (! $result->successful) {
                    $this->error("Failed to renew {$gameMap->api_match_id} (ID : {$gameMap->id}): {$result->message()}");

                    continue;
                }
            }

            $this->info("Map {$gameMap->api_match_id} (ID : {$gameMap->id}) renewed.");
        }

        $this->info('Maps have been renewed.');
    }
}
