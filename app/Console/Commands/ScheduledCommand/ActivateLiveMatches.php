<?php

/**
 * GC-Stats — Live-activation command
 *
 * Flips a match from `upcoming` to `live` right as its scheduled_at hits —
 * only matches scheduled within the last 5 minutes are eligible, so a
 * match that's simply been sitting in the past (imported historical data,
 * a schedule that was never updated, a match nobody marked finished) is
 * never swept up by a run of this command; it only catches kickoffs the
 * command's own per-minute schedule is actually watching for in real time.
 * Matches carrying the MatchDisplay::UNKNOWN_DATE placeholder (imported
 * matches without a real kickoff time) are excluded outright. Also pings a
 * dedicated Discord webhook on activation. Usage: php artisan matches:activate-live
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Models\Matchs;
use App\Support\MatchDisplay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivateLiveMatches extends Command
{
    protected $signature = 'matches:activate-live';

    protected $description = 'Flip upcoming matches to live once their scheduled time has passed, and notify Discord';

    public function handle(): int
    {
        $matches = Matchs::query()
            ->where('status', 'upcoming')
            ->whereBetween('scheduled_at', [now()->subMinutes(5), now()])
            ->whereDate('scheduled_at', '!=', MatchDisplay::UNKNOWN_DATE)
            ->with(['teamA', 'teamB', 'tournament', 'tournamentPhase'])
            ->get();

        if ($matches->isEmpty()) {
            $this->info('No match to activate.');

            return self::SUCCESS;
        }

        foreach ($matches as $match) {
            $match->update(['status' => 'live']);

            activity('tournament')->performedOn($match)->log('match.auto_activated');

            $this->notifyDiscord($match);

            $this->info("Match #{$match->id} activated.");
        }

        return self::SUCCESS;
    }

    private function notifyDiscord(Matchs $match): void
    {
        $webhookUrl = config('services.discord.match_live_webhook');

        if (! $webhookUrl) {
            return;
        }

        $teamA = MatchDisplay::teamShortName($match->teamA, $match->status);
        $teamB = MatchDisplay::teamShortName($match->teamB, $match->status);
        $tournament = $match->tournament->name ?? 'Unknown tournament';
        $phase = $match->tournamentPhase->name ?? null;

        $content = "🔴 **LIVE** — {$teamA} vs {$teamB}\n{$tournament}".($phase ? " — {$phase}" : '');

        try {
            Http::post($webhookUrl, ['content' => $content]);
        } catch (\Throwable $e) {
            Log::warning('Discord match-live webhook failed', [
                'match_id' => $match->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
