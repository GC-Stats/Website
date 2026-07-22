<?php

/**
 * GC-Stats — Import a player from the HenrikDev API
 *
 * Artisan command that imports or updates a player's data
 * (stats, team, country, etc.) from the public HenrikDev API (VLR esports).
 * Usage: php artisan import:player {player_id}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ImportCommands;

use App\Models\Player;
use App\Support\PersonName;
use App\Support\SocialsParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPlayer extends Command
{
    protected $signature = 'import:player {player_id : VLR Player ID}';

    protected $description = 'Import or update a player from HenrikDev API';

    private string $baseUrl = 'https://api.henrikdev.xyz/valorant/v2/esports/vlr';

    public function handle(): int
    {
        $playerId = $this->argument('player_id');
        $this->info("Importing player: {$playerId}");

        try {
            $data = $this->fetchPlayer($playerId);
            $player = $this->upsertPlayer($data);

            $this->info("✅ Player: {$player->handle} (ID {$player->id})");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error: {$e->getMessage()} at line {$e->getLine()}");

            return self::FAILURE;
        }
    }

    private function fetchPlayer(string $playerId): array
    {
        $response = Http::timeout(15)->withHeaders([
            'Authorization' => config('services.henrikdev.key'),
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/players/{$playerId}");

        if ($response->failed()) {
            throw new \RuntimeException("API error: {$response->status()}");
        }

        return $response->json('data') ?? [];
    }

    private function upsertPlayer(array $data): Player
    {
        $nameParts = PersonName::split($data['real_name'] ?? '');
        $socials = SocialsParser::parse($data['socials'] ?? []);

        return Player::updateOrCreate(
            ['vlr_id' => $data['id']],
            [
                'handle' => $data['name'],
                'first_name' => $nameParts['first'],
                'last_name' => $nameParts['last'],
                'country_code' => $data['country']['code'] ?? null,
                'socials' => $socials,
            ]
        );
    }
}
