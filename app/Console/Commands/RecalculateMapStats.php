<?php

/**
 * GC-Stats — Recalculate map player stats
 *
 * Recomputes each player's ACS, ADR, KAST% and HS% for a map purely from the
 * already-persisted per-round data (game_map_rounds, game_map_round_kills,
 * game_map_round_damages, game_map_round_player_stats) and overwrites the
 * matching game_player_stats row. Unlike BackfillMapAdvancedStats, this
 * never calls the Riot API — it's for repairing/re-deriving stats after a
 * bug fix in the aggregation logic, using data already on file.
 *
 * KAST/trade-window logic mirrors App\Services\MapStatsCalculator, adapted
 * to this schema's team_id-based rounds (no Riot-style team "color") instead
 * of raw Riot JSON.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Models\GameMap;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RecalculateMapStats extends Command
{
    private const TRADE_WINDOW_MS = 3000;

    protected $signature = 'stats:recalculate
        {--map= : Only process this game map ID}
        {--tournament= : Only process maps for this tournament ID}
        {--limit= : Max number of maps to process}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Recalculate ACS/ADR/KAST%/HS% for game player stats from persisted round data';

    public function handle(): int
    {
        $query = GameMap::query()
            ->whereHas('rounds.kills')
            ->with(['rounds.kills', 'rounds.damages', 'rounds.playerStats', 'playerStats']);

        if ($mapId = $this->option('map')) {
            $query->where('id', (int) $mapId);
        }

        if ($tournamentId = $this->option('tournament')) {
            $query->where('tournament_id', (int) $tournamentId);
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $maps = $query->get();
        $this->info("Found {$maps->count()} map(s) to recalculate.");

        $updated = 0;
        $rows = [];

        foreach ($maps as $map) {
            $rounds = $map->rounds;
            $totalRounds = $rounds->count();

            if ($totalRounds === 0) {
                continue;
            }

            foreach ($map->playerStats as $playerStat) {
                if (! $playerStat->player_id) {
                    continue;
                }

                $computed = $this->computeStatsForPlayer($playerStat->player_id, $rounds, $totalRounds);

                $rows[] = [
                    $map->id,
                    $playerStat->player_id,
                    "{$playerStat->acs} -> {$computed['acs']}",
                    "{$playerStat->adr} -> {$computed['adr']}",
                    "{$playerStat->kast_percentage} -> {$computed['kast_percentage']}",
                    "{$playerStat->headshot_percentage} -> {$computed['headshot_percentage']}",
                ];

                if (! $this->option('dry-run')) {
                    $playerStat->update($computed);
                }

                $updated++;
            }
        }

        if (! empty($rows)) {
            $this->table(['Map', 'Player', 'ACS', 'ADR', 'KAST%', 'HS%'], $rows);
        }

        $mode = $this->option('dry-run') ? 'Would update' : 'Updated';
        $this->info("{$mode} {$updated} player stat row(s) across {$maps->count()} map(s).");

        return self::SUCCESS;
    }

    /**
     * @return array{acs:float,adr:float,kast_percentage:float,first_kills:int,first_deaths:int,headshot_percentage:float}
     */
    private function computeStatsForPlayer(int $playerId, Collection $rounds, int $totalRounds): array
    {
        $totalScore = 0;
        $totalDamage = 0;
        $headshots = 0;
        $bodyshots = 0;
        $legshots = 0;
        $kastRounds = 0;
        $firstKills = 0;
        $firstDeaths = 0;

        foreach ($rounds as $round) {
            $kills = $round->kills->sortBy('time_ms')->values();
            $damages = $round->damages;
            $roundStat = $round->playerStats->firstWhere('player_id', $playerId);

            $totalScore += $roundStat?->score ?? 0;

            $playerTeamId = $roundStat?->team_id;

            $killsInRound = $kills->where('killer_player_id', $playerId)->count();
            $assistsInRound = $kills->filter(fn ($k) => in_array($playerId, $k->assistant_player_ids ?? []))->count();

            $death = $kills->firstWhere('victim_player_id', $playerId);
            $survived = ! $death;

            $traded = false;
            if ($death && $death->killer_player_id) {
                $killerId = $death->killer_player_id;
                $timeOfDeath = $death->time_ms;

                $traded = $kills->contains(fn ($k) => $k->victim_player_id === $killerId
                    && $k->killer_player_id !== null
                    && $this->teamIdFor($k->killer_player_id, $round) === $playerTeamId
                    && $k->time_ms >= $timeOfDeath
                    && $k->time_ms <= $timeOfDeath + self::TRADE_WINDOW_MS);
            }

            if ($killsInRound > 0 || $assistsInRound > 0 || $survived || $traded) {
                $kastRounds++;
            }

            $firstKill = $kills->first();
            if ($firstKill) {
                if ($firstKill->killer_player_id === $playerId) {
                    $firstKills++;
                }
                if ($firstKill->victim_player_id === $playerId) {
                    $firstDeaths++;
                }
            }

            foreach ($damages->where('attacker_player_id', $playerId) as $damage) {
                $totalDamage += $damage->damage;
                $headshots += $damage->headshots;
                $bodyshots += $damage->bodyshots;
                $legshots += $damage->legshots;
            }
        }

        $totalShots = $headshots + $bodyshots + $legshots;

        return [
            'acs' => round($totalScore / $totalRounds),
            'adr' => round($totalDamage / $totalRounds),
            'kast_percentage' => round($kastRounds / $totalRounds * 100, 2),
            'first_kills' => $firstKills,
            'first_deaths' => $firstDeaths,
            'headshot_percentage' => $totalShots > 0 ? round($headshots / $totalShots * 100, 2) : 0,
        ];
    }

    private function teamIdFor(int $playerId, $round): ?int
    {
        return $round->playerStats->firstWhere('player_id', $playerId)?->team_id;
    }
}
