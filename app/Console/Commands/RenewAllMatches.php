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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RenewAllMatches extends Command
{
    protected $signature = 'val:matches:renew';

    protected $description = 'Renew every matches cache';

    public function handle()
    {
        $query = GameMap::query()
            ->whereNotNull('api_match_id');

        $maps = $query->get();

        $this->info("Found {$maps->count()} map(s) to renew.");

        foreach ($maps as $gameMap) {
            usleep((int) 500);

            $region = config('regions.riot_api.'.$gameMap->match->tournament->region);
            $relayUrl = rtrim(config('services.riot.relay_url'), '/');

            $response = Http::withHeaders(['Authorization' => config('services.riot.relay_token')])
                ->post("{$relayUrl}/match/{$region}/{$gameMap->api_match_id}/renew");

            if (! $response->successful()) {
                $response = Http::withHeaders(['Authorization' => config('services.riot.relay_token')])
                    ->post("{$relayUrl}/match/esports/{$gameMap->api_match_id}/renew");

                if (! $response->successful()) {
                    if ($response->status() == 404) {
                        $this->info("Map {$gameMap->api_match_id} (ID : {$gameMap->id}) not found.");
                    } else {
                        $this->error("Failed to fetch {$gameMap->api_match_id} (ID : {$gameMap->id}), code {$response->status()}, reason: {$response->body()}");
                    }

                    continue;
                }
            }

            $this->info("Map {$gameMap->api_match_id} (ID : {$gameMap->id}) renewed.");
        }

        $this->info('Maps have been renewed.');
    }
}
