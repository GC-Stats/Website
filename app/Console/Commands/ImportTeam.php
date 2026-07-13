<?php

/**
 * GC-Stats — Import a team from the HenrikDev API
 *
 * Artisan command that imports or updates a team's data (roster, logo,
 * transactions, etc.) from the public HenrikDev API (VLR esports).
 * Usage: php artisan import:team {team_id}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Http\Controllers\Api\ApiTeamLogoController;
use App\Models\Player;
use App\Models\Team;
use App\Support\PersonName;
use App\Support\SocialsParser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportTeam extends Command
{
    protected $signature = 'import:team {team_id : VLR Team ID}';

    protected $description = 'Import a team with roster and transactions from HenrikDev API';

    private string $baseUrl = 'https://api.henrikdev.xyz/valorant/v2/esports/vlr';

    public function handle(): int
    {
        $teamId = $this->argument('team_id');
        $this->info("Importing team: {$teamId}");

        try {
            $teamData = $this->fetchTeam($teamId);
            $transactions = $this->fetchTransactions($teamId);

            DB::transaction(function () use ($teamData, $transactions) {
                $team = $this->upsertTeam($teamData);
                $this->info("  Team: {$team->name} (ID {$team->id})");

                if (($teamData['logo'] ?? null) !== 'https://www.vlr.gg/img/vlr/tmp/vlr.png' && ! empty($teamData['logo'])) {
                    $this->uploadLogo($teamData['logo'], $team->id);
                }

                $this->importRoster($teamData['roster'] ?? [], $team);
                $this->importTransactions($transactions, $team);
            });

            $this->info('✅ Import complete.');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error: {$e->getMessage()} at line {$e->getLine()}");

            return self::FAILURE;
        }
    }

    public function uploadLogo(string $url, int $teamId): void
    {
        $tmpPath = null;

        try {
            $response = Http::timeout(15)->get($url);

            if ($response->failed()) {
                throw new \RuntimeException("Failed to download logo: HTTP {$response->status()}");
            }

            $tmpPath = tempnam(sys_get_temp_dir(), 'logo');
            file_put_contents($tmpPath, $response->body());

            $file = new UploadedFile($tmpPath, 'logo.png', null, null, true);

            $request = Request::create("/internal/teams/{$teamId}/logos", 'POST', [
                'team_id' => $teamId,
                'accept' => true,
            ], [], ['image' => $file]);

            $uploadResponse = app(ApiTeamLogoController::class)->upload($request, $teamId);

            if ($uploadResponse->getStatusCode() >= 400) {
                throw new \RuntimeException("Local API error: {$uploadResponse->getStatusCode()} {$uploadResponse->getContent()}");
            }

            $this->info("  Logo Imported (ID {$teamId})");
        } catch (\Throwable $e) {
            $this->error('Error import logo : '.$e->getMessage());
            Log::error('import:team — logo import failed', [
                'team_id' => $teamId,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if ($tmpPath) {
                @unlink($tmpPath);
            }
        }
    }

    private function fetchTeam(string $teamId): array
    {
        $response = Http::timeout(15)->withHeaders([
            'Authorization' => config('services.henrikdev.key'),
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/teams/{$teamId}");

        if ($response->failed()) {
            throw new \RuntimeException("API error: {$response->status()}");
        }

        return $response->json('data') ?? [];
    }

    private function fetchTransactions(string $teamId): array
    {
        sleep(1);

        $response = Http::timeout(15)->withHeaders([
            'Authorization' => config('services.henrikdev.key'),
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/teams/{$teamId}/transactions");

        if ($response->failed()) {
            throw new \RuntimeException("API error transactions: {$response->status()}");
        }

        return $response->json('data') ?? [];
    }

    private function upsertTeam(array $data): Team
    {
        return Team::updateOrCreate(
            ['vlr_id' => $data['id']],
            [
                'short_name' => $data['tag'] ?? null,
                'country_code' => $data['country']['code'] ?? null,
                'socials' => SocialsParser::parse($data['socials'] ?? []),
                'is_active' => true,
            ]
        );
    }

    private function importRoster(array $roster, Team $team): void
    {
        foreach ($roster as $member) {
            $player = $this->upsertPlayer($member);

            $isActive = \DB::table('player_team')
                ->where('player_id', $player->id)
                ->where('team_id', $team->id)
                ->whereNull('left_at')
                ->exists();

            if (! $isActive) {
                \DB::table('player_team')->insert([
                    'player_id' => $player->id,
                    'team_id' => $team->id,
                    'role' => $this->normalizeRole($member['role'] ?? ''),
                    'joined_at' => now()->toDateString(),
                    'left_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->line("  Player: {$player->handle}");
        }
    }

    private function upsertPlayer(array $data): Player
    {
        $nameParts = PersonName::split($data['real_name'] ?? '');

        return Player::updateOrCreate(
            ['vlr_id' => $data['id']],
            [
                'handle' => $data['alias'],
                'first_name' => $nameParts['first'],
                'last_name' => $nameParts['last'],
                'country_code' => $data['country_code'] ?? null,
            ]
        );
    }

    private function importTransactions(array $transactions, Team $team): void
    {
        $sorted = collect($transactions)->sortBy('date');

        foreach ($sorted as $transaction) {
            $action = strtolower($transaction['action'] ?? '');
            $playerData = $transaction['player'] ?? [];
            $date = $this->parseDate($transaction['date'] ?? null);

            if (empty($playerData['alias'])) {
                continue;
            }

            $player = $this->upsertPlayerFromTransaction($playerData);

            if ($action === 'join') {
                \DB::table('player_team')->insert([
                    'player_id' => $player->id,
                    'team_id' => $team->id,
                    'role' => $this->normalizeRole($transaction['position'] ?? ''),
                    'joined_at' => $date ?? now()->toDateString(),
                    'left_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line("  ↑ Joined: {$player->handle} ({$date})");

            } elseif ($action === 'leave') {
                $updated = \DB::table('player_team')
                    ->where('player_id', $player->id)
                    ->where('team_id', $team->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => $date, 'updated_at' => now()]);

                if ($updated) {
                    $this->line("  ↓ Left: {$player->handle} ({$date})");
                } else {
                    $this->warn("  ⚠ Leave without join: {$player->handle} ({$date})");
                }
            }
        }
    }

    private function upsertPlayerFromTransaction(array $playerData): Player
    {
        return Player::updateOrCreate(
            ['vlr_id' => $playerData['id']],
            [
                'handle' => $playerData['alias'],
                'country_code' => $playerData['country_code'] ?? null,
            ]
        );
    }

    private function normalizeRole(?string $role): string
    {
        return match (strtolower(trim($role ?? ''))) {
            'igl', 'in-game leader' => 'igl',
            'coach', 'head coach' => 'head coach',
            'assistant coach' => 'assistant coach',
            'substitute', 'sub' => 'sub',
            default => 'player',
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
