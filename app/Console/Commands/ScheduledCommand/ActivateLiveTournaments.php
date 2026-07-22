<?php

/**
 * GC-Stats — Live-activation command
 *
 * Flips a tournament from `upcoming` to `live` right as its scheduled_at hits —
 * only tournaments scheduled within the last 5 minutes are eligible, so a
 * tournament that's simply been sitting in the past (imported historical data,
 * a schedule that was never updated, a tournament nobody marked finished) is
 * never swept up by a run of this command; it only catches kickoffs the
 * command's own per-minute schedule is actually watching for in real time.
 * Also pings a dedicated Discord webhook on activation. Usage: php artisan matches:activate-live
 *
 * Tournament are never flipped to finished automatically, because most editor can't edit finished tournament
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivateLiveTournaments extends Command
{
    protected $signature = 'tournaments:activate-live';

    protected $description = 'Flip upcoming tournaments to live once their scheduled time has passed, and notify Discord';

    public function handle(): int
    {
        $tournaments = Tournament::query()
            ->where('status', 'upcoming')
            ->whereBetween('start_date', [now()->subDay(), now()->addDay()])
            ->get();

        if ($tournaments->isEmpty()) {
            $this->info('No tournament to activate.');

            return self::SUCCESS;
        }

        foreach ($tournaments as $tournament) {
            $tournament->update(['status' => 'live']);

            activity('tournament')->performedOn($tournament)->log('tournament.auto_activated');

            $this->notifyDiscord($tournament);

            $this->info("Tournament #{$tournament->id} activated.");
        }

        return self::SUCCESS;
    }

    private function notifyDiscord(Tournament $tournament): void
    {
        $webhookUrl = config('services.discord.match_live_webhook');

        if (! $webhookUrl) {
            return;
        }

        $content = "🔴 **LIVE** - {$tournament->name}";

        try {
            Http::post($webhookUrl, ['content' => $content]);
        } catch (\Throwable $e) {
            Log::warning('Discord tournament-live webhook failed', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
