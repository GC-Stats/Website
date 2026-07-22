<?php

/**
 * GC-Stats — Import a team logo from the HenrikDev API
 *
 * Artisan command that fetches a team's logo URL from the public HenrikDev
 * API (VLR esports) and uploads it through the local internal API, only
 * when the team does not already have a current logo.
 * Usage: php artisan import:team:logo {team_id}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ImportCommands;

use App\Http\Controllers\Api\ApiTeamLogoController;
use App\Models\Logo;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ImportTeamLogo extends Command
{
    protected $signature = 'import:team:logo {team_id : VLR Team ID}';

    protected $description = 'Import a team logo from HenrikDev API through the local API, only if no logo exists yet';

    private string $baseUrl = 'https://api.henrikdev.xyz/valorant/v2/esports/vlr';

    public function handle(): int
    {
        $vlrId = $this->argument('team_id');

        $team = Team::where('vlr_id', $vlrId)->first();

        if (! $team) {
            $this->error("Team not found for VLR ID {$vlrId}");

            return self::FAILURE;
        }

        if ($this->hasLogo($team)) {
            $this->info("  Logo already exists for {$team->name} (ID {$team->id}), skipping.");

            return self::SUCCESS;
        }

        try {
            $logoUrl = $this->fetchLogoUrl($vlrId);

            if (! $logoUrl || $logoUrl === 'https://www.vlr.gg/img/vlr/tmp/vlr.png') {
                $this->warn("  No logo available for {$team->name} (ID {$team->id}).");

                return self::SUCCESS;
            }

            $this->uploadLogo($logoUrl, $team->id);

            $this->info("✅ Logo imported for {$team->name} (ID {$team->id}).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Error: {$e->getMessage()} at line {$e->getLine()}");

            return self::FAILURE;
        }
    }

    private function hasLogo(Team $team): bool
    {
        return Logo::where('entity_type', 'team')
            ->where('entity_id', $team->id)
            ->whereNull('until')
            ->exists();
    }

    private function fetchLogoUrl(string $vlrId): ?string
    {
        $response = Http::timeout(15)->withHeaders([
            'Authorization' => config('services.henrikdev.key'),
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/teams/{$vlrId}");

        if ($response->failed()) {
            throw new \RuntimeException("API error: {$response->status()}");
        }

        return $response->json('data.logo');
    }

    private function uploadLogo(string $url, int $teamId): void
    {
        $response = Http::timeout(15)->get($url);

        if ($response->failed()) {
            throw new \RuntimeException("Unable to download logo from {$url}: HTTP {$response->status()}");
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($tmpPath, $response->body());

        try {
            $file = new UploadedFile($tmpPath, 'logo.png', null, null, true);

            $request = Request::create("/internal/teams/{$teamId}/logos", 'POST', [
                'team_id' => $teamId,
                'accept' => true,
            ], [], ['image' => $file]);

            $response = app(ApiTeamLogoController::class)->upload($request, $teamId);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException("Local API error: {$response->getStatusCode()} {$response->getContent()}");
            }
        } finally {
            @unlink($tmpPath);
        }

        $this->info("  Logo Imported (ID {$teamId})");
    }
}
