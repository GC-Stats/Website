<?php

/**
 * GC-Stats — Player POV stream detection command
 *
 * For every match approaching or currently live, checks Twitch for the
 * relevant players' and teams' channels (Player::socials['twitch'] /
 * Team::socials['twitch']) — the current roster of each team plus every
 * player who appeared in that team's last 5 matches — and records a
 * App\Models\MatchPlayerPov row for any channel found live with a title
 * containing the tournament's player_pov_phrase (Tournament::
 * player_pov_phrase, case-insensitive).
 *
 * Runs every minute (see bootstrap/app.php), which is what covers a
 * delayed kickoff on its own: an 'upcoming' match stays eligible for as
 * long as its scheduled_at is in the past (see the query below), so a
 * match held up by an unfinished previous one just keeps getting checked
 * until it actually goes live and eventually finishes. Usage:
 * php artisan matches:detect-player-povs
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Models\GamePlayerStat;
use App\Models\MatchPlayerPov;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Services\TwitchService;
use App\Support\MatchDisplay;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DetectPlayerPovStreams extends Command
{
    protected $signature = 'matches:detect-player-povs';

    protected $description = 'Detect live Player POV Twitch streams for upcoming/live matches and record them';

    /** How many of a team's most recent matches count towards "recently played" rosters. */
    private const RECENT_MATCHES = 5;

    /** Start checking this long before the scheduled kickoff. */
    private const PRE_MATCH_WINDOW_MINUTES = 20;

    /** Safety net: stop checking a still-'upcoming' match this stale — covers stuck/bad data, not a real delay. */
    private const MAX_UPCOMING_AGE_HOURS = 6;

    public function handle(TwitchService $twitch): int
    {
        $matches = Matchs::query()
            ->whereIn('matches.status', ['upcoming', 'live'])
            ->whereDate('matches.scheduled_at', '!=', MatchDisplay::UNKNOWN_DATE)
            ->where(function ($query) {
                $query->where('matches.status', 'live')
                    ->orWhere(function ($query) {
                        $query->where('matches.status', 'upcoming')
                            ->where('matches.scheduled_at', '<=', now()->addMinutes(self::PRE_MATCH_WINDOW_MINUTES))
                            ->where('matches.scheduled_at', '>=', now()->subHours(self::MAX_UPCOMING_AGE_HOURS));
                    });
            })
            ->join('tournaments', 'tournaments.id', '=', 'matches.tournament_id')
            ->where('tournaments.active', true)
            ->whereNotNull('tournaments.player_pov_phrase')
            ->where('tournaments.player_pov_phrase', '!=', '')
            ->with(['tournament', 'teamA', 'teamB'])
            ->select('matches.*')
            ->get();

        if ($matches->isEmpty()) {
            $this->info('No match to check.');

            return self::SUCCESS;
        }

        foreach ($matches as $match) {
            $this->detectForMatch($match, $twitch);
        }

        return self::SUCCESS;
    }

    private function detectForMatch(Matchs $match, TwitchService $twitch): void
    {
        $phrase = Str::lower($match->tournament->player_pov_phrase);

        $candidates = collect();

        foreach ([$match->teamA, $match->teamB] as $team) {
            if (! $team) {
                continue;
            }

            $candidates = $candidates->merge($this->candidatesForTeam($team, $match));
        }

        $candidates = $candidates->unique('login');

        if ($candidates->isEmpty()) {
            return;
        }

        $liveStreams = $twitch->getLiveStreams($candidates->pluck('login')->all());

        if ($liveStreams->isEmpty()) {
            return;
        }

        $changed = false;

        foreach ($candidates as $candidate) {
            $stream = $liveStreams->get($candidate['login']);

            if (! $stream || ! str_contains(Str::lower($stream['title']), $phrase)) {
                continue;
            }

            $pov = MatchPlayerPov::updateOrCreate(
                ['match_id' => $match->id, 'twitch_login' => $candidate['login']],
                [
                    'team_id' => $candidate['team_id'],
                    'player_id' => $candidate['player_id'],
                    'title' => $stream['title'],
                    'url' => $stream['url'],
                    'last_seen_live_at' => now(),
                ]
            );

            // Only the newly-created/changed rows warrant busting the match
            // cache below — refreshing last_seen_live_at on an already-known,
            // still-live, unchanged stream isn't worth a cache bust.
            $changed = $changed || $pov->wasRecentlyCreated || $pov->wasChanged(['title', 'url']);
        }

        if ($changed) {
            // The public match page's cache key is derived from the match's
            // own updated_at (see MatchController::buildMatchPayload), and
            // touch()-ing it fires MatchObserver::saved() (CDN purge +
            // related cache tags). That's what makes this safe across our
            // two separate Redis instances without any explicit cross-store
            // invalidation: both instances read the same replicated
            // `matches` row, so both independently compute the new
            // cache key on their next request — no need to push a flush to
            // either store directly. Same idiom as MatchVodController/
            // MatchStreamController.
            $match->touch();
        }
    }

    /**
     * @return Collection<int, array{login: string, team_id: int, player_id: ?int}>
     */
    private function candidatesForTeam(Team $team, Matchs $match): Collection
    {
        $candidates = collect();

        $teamLogin = $team->socials['twitch'] ?? null;
        if ($teamLogin) {
            $candidates->push(['login' => Str::lower($teamLogin), 'team_id' => $team->id, 'player_id' => null]);
        }

        $playerIds = $team->currentPlayers()->pluck('players.id')
            ->merge($this->recentPlayerIds($team, $match))
            ->unique();

        Player::query()
            ->whereIn('id', $playerIds)
            ->get(['id', 'socials'])
            ->each(function (Player $player) use ($team, $candidates) {
                $login = $player->socials['twitch'] ?? null;

                if ($login) {
                    $candidates->push(['login' => Str::lower($login), 'team_id' => $team->id, 'player_id' => $player->id]);
                }
            });

        return $candidates;
    }

    /** Distinct player ids who played for this team in its last N matches before the given one. */
    private function recentPlayerIds(Team $team, Matchs $match): array
    {
        $recentMatchIds = Matchs::query()
            ->where('status', 'finished')
            ->where('id', '!=', $match->id)
            ->where(function ($query) use ($team) {
                $query->where('team_a_id', $team->id)->orWhere('team_b_id', $team->id);
            })
            ->orderByDesc('scheduled_at')
            ->limit(self::RECENT_MATCHES)
            ->pluck('id');

        if ($recentMatchIds->isEmpty()) {
            return [];
        }

        return GamePlayerStat::query()
            ->whereIn('match_id', $recentMatchIds)
            ->where('team_id', $team->id)
            ->distinct()
            ->pluck('player_id')
            ->all();
    }
}
