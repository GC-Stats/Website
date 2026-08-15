<?php

/**
 * GC-Stats — Match stats aggregation service
 *
 * Match-total (all maps combined) player/team stats for one match — the
 * shape App\Models\ForumMessage's match embed variants (scoreboard/
 * performance/economy/player) render, letting a forum post link "this
 * player's score in this match" or the full scoreboard instead of a
 * screenshot.
 *
 * Deliberately its own self-contained queries rather than reusing
 * MatchController::index()'s per-map closure: that closure builds a much
 * richer per-map breakdown feeding a heavily cached page render, and
 * re-threading match-only totals through it risked destabilizing that
 * cache-sensitive path for a feature that only needs the totals — same
 * source tables/columns, computed independently instead. Nothing here is
 * cached: computed fresh per embed render, same call as
 * ForumMessage::resolveEmbedStats() already makes for player/team embeds.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Matchs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchStatsService
{
    private const ECO_TIERS = [
        'eco' => ['min' => 0, 'max' => 5000, 'label' => 'Eco'],
        'semi_eco' => ['min' => 5001, 'max' => 10000, 'label' => 'Semi-Eco'],
        'semi_buy' => ['min' => 10001, 'max' => 20000, 'label' => 'Semi-Buy'],
        'full_buy' => ['min' => 20001, 'max' => 1000000, 'label' => 'Full Buy'],
    ];

    /**
     * @return array{stats_a: list<array>, stats_b: list<array>, performance: array<int, array>, eco_summary: array{team_a: array, team_b: array}}
     */
    public function aggregateFor(Matchs $match): array
    {
        $rows = DB::table('game_player_stats')
            ->join('players', 'game_player_stats.player_id', '=', 'players.id')
            ->where('game_player_stats.match_id', $match->id)
            ->select(['game_player_stats.*', 'players.handle as player_handle'])
            ->get();

        [$idsA, $idsB] = $this->splitByTeam($rows, $match);

        return [
            'stats_a' => $this->aggregatePlayerRows($rows, $idsA),
            'stats_b' => $this->aggregatePlayerRows($rows, $idsB),
            'performance' => $this->performanceFor($match->id),
            'eco_summary' => $this->ecoSummaryFor($match, $idsA, $idsB),
        ];
    }

    /**
     * One player's match-total stat line — the "score d'une personne"
     * embed. Null if the player didn't play in this match.
     */
    public function playerStatsFor(Matchs $match, int $playerId): ?array
    {
        $aggregate = $this->aggregateFor($match);

        return collect($aggregate['stats_a'])->merge($aggregate['stats_b'])
            ->firstWhere('player_id', $playerId);
    }

    /**
     * @return array{0: list<int>, 1: list<int>}
     */
    private function splitByTeam(Collection $rows, Matchs $match): array
    {
        $idsA = [];
        $idsB = [];

        foreach ($rows->groupBy('player_id') as $playerId => $playerRows) {
            $majorityTeamId = $playerRows->countBy('team_id')->sortDesc()->keys()->first();

            if ($majorityTeamId == $match->team_a_id) {
                $idsA[] = (int) $playerId;
            } elseif ($majorityTeamId == $match->team_b_id) {
                $idsB[] = (int) $playerId;
            }
        }

        return [$idsA, $idsB];
    }

    private function aggregatePlayerRows(Collection $rows, array $ids): array
    {
        $grouped = $rows->whereIn('player_id', $ids)->groupBy('player_id');

        $result = [];
        foreach ($grouped as $playerId => $playerRows) {
            $result[] = [
                'player_id' => (int) $playerId,
                'player_handle' => $playerRows->first()->player_handle,
                'acs' => (int) round($playerRows->avg('acs')),
                'kills' => (int) $playerRows->sum('kills'),
                'deaths' => (int) $playerRows->sum('deaths'),
                'assists' => (int) $playerRows->sum('assists'),
                'adr' => (int) round($playerRows->avg('adr')),
                'kast_percentage' => round($playerRows->avg('kast_percentage'), 2),
                'headshot_percentage' => round($playerRows->avg('headshot_percentage'), 2),
                'maps_played' => $playerRows->count(),
            ];
        }

        return collect($result)->sortByDesc('acs')->values()->all();
    }

    private function performanceFor(int $matchId): array
    {
        $rows = DB::table('game_map_round_player_stats as ps')
            ->join('game_map_rounds as r', 'ps.game_map_round_id', '=', 'r.id')
            ->where('r.match_id', $matchId)
            ->select([
                'ps.player_id',
                DB::raw('SUM(CASE WHEN ps.kills = 2 THEN 1 ELSE 0 END) as k2'),
                DB::raw('SUM(CASE WHEN ps.kills = 3 THEN 1 ELSE 0 END) as k3'),
                DB::raw('SUM(CASE WHEN ps.kills = 4 THEN 1 ELSE 0 END) as k4'),
                DB::raw('SUM(CASE WHEN ps.kills >= 5 THEN 1 ELSE 0 END) as k5'),
                DB::raw("SUM(CASE WHEN ps.weapon_id = 'Sheriff' THEN ps.kills ELSE 0 END) as sheriff_kills"),
            ])
            ->groupBy('ps.player_id')
            ->get();

        $performance = [];
        foreach ($rows as $row) {
            $performance[(int) $row->player_id] = [
                '2k' => (int) $row->k2,
                '3k' => (int) $row->k3,
                '4k' => (int) $row->k4,
                '5k' => (int) $row->k5,
                'sheriff_kills' => (int) $row->sheriff_kills,
            ];
        }

        return $performance;
    }

    /**
     * Round counts/win counts per economy tier, per team — same tiers/logic
     * as MatchController::index()'s $ecoTiers, kept in sync manually since
     * that closure isn't reusable as a service (see class docblock).
     *
     * @param  list<int>  $idsA
     * @param  list<int>  $idsB
     */
    private function ecoSummaryFor(Matchs $match, array $idsA, array $idsB): array
    {
        $sqlIdsA = count($idsA) ? implode(',', array_map('intval', $idsA)) : '0';
        $sqlIdsB = count($idsB) ? implode(',', array_map('intval', $idsB)) : '0';

        $rounds = DB::table('game_map_round_player_stats as ps')
            ->join('game_map_rounds as r', 'ps.game_map_round_id', '=', 'r.id')
            ->where('r.match_id', $match->id)
            ->select([
                'r.id as round_id',
                'r.winning_team as winning_team_id',
                DB::raw("SUM(CASE WHEN ps.player_id IN ({$sqlIdsA}) THEN ps.loadout_value ELSE 0 END) as spent_a"),
                DB::raw("SUM(CASE WHEN ps.player_id IN ({$sqlIdsB}) THEN ps.loadout_value ELSE 0 END) as spent_b"),
            ])
            ->groupBy('r.id', 'r.winning_team')
            ->get();

        $summary = ['team_a' => [], 'team_b' => []];

        foreach (['team_a', 'team_b'] as $teamKey) {
            $spentField = $teamKey === 'team_a' ? 'spent_a' : 'spent_b';
            $targetTeamId = $teamKey === 'team_a' ? $match->team_a_id : $match->team_b_id;

            foreach (self::ECO_TIERS as $tierKey => $tier) {
                $roundsInTier = $rounds->filter(fn ($r) => $tier['min'] <= $r->$spentField && $tier['max'] >= $r->$spentField);

                $summary[$teamKey][$tierKey] = [
                    'label' => $tier['label'],
                    'total' => $roundsInTier->count(),
                    'win' => $roundsInTier->where('winning_team_id', $targetTeamId)->count(),
                ];
            }
        }

        return $summary;
    }
}
