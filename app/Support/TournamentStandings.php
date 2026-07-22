<?php

/**
 * GC-Stats — Swiss/round-robin standings computation
 *
 * Shared by the standings tables and the leaderboard component so the
 * win/loss/map/round differential math (and its sort order) lives in one
 * place instead of being copy-pasted across Blade files.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use Illuminate\Support\Collection;

class TournamentStandings
{
    /**
     * @param  array<int, array<string, mixed>>  $matches
     * @param  array<int, array<string, mixed>>  $teams
     * @param  bool  $useBuchholz  Whether to insert the Buchholz score (sum of each opponent's
     *                             total wins) as the tie-break right after match differential —
     *                             used by the swiss_buchholz format.
     */
    public static function compute(array $matches, array $teams, bool $useBuchholz = false): Collection
    {
        $matchesColl = collect($matches);
        $allTeamsColl = collect($teams);
        $standings = collect();

        $teamIdsInPhase = $matchesColl->flatMap(function ($m) {
            return [(string) ($m['team_a_id'] ?? ''), (string) ($m['team_b_id'] ?? '')];
        })->filter()->unique()->toArray();

        $phaseTeams = $allTeamsColl->filter(function ($t) use ($teamIdsInPhase) {
            return in_array((string) ($t['id'] ?? ''), $teamIdsInPhase);
        });

        $winsByTeamId = [];
        $opponentIdsByTeamId = [];

        foreach ($phaseTeams as $team) {
            $teamId = (string) $team['id'];

            $teamMatches = $matchesColl->filter(function ($m) use ($teamId) {
                return (string) ($m['team_a_id'] ?? '') === $teamId
                    || (string) ($m['team_b_id'] ?? '') === $teamId;
            });

            $wins = 0;
            $losses = 0;
            $mapWins = 0;
            $mapLosses = 0;
            $roundWins = 0;
            $roundLosses = 0;
            $opponentIds = [];

            foreach ($teamMatches as $m) {
                $scoreA = $m['team_a_score'] ?? null;
                $scoreB = $m['team_b_score'] ?? null;

                if ($scoreA === null || $scoreB === null) {
                    continue;
                }

                $isTeamA = (string) ($m['team_a_id'] ?? '') === $teamId;
                $myScore = $isTeamA ? $scoreA : $scoreB;
                $theirScore = $isTeamA ? $scoreB : $scoreA;
                $opponentIds[] = (string) ($isTeamA ? ($m['team_b_id'] ?? '') : ($m['team_a_id'] ?? ''));

                if ($myScore > $theirScore) {
                    $wins++;
                } elseif ($myScore < $theirScore) {
                    $losses++;
                }

                foreach (($m['game_maps'] ?? []) as $map) {
                    $mapScoreA = $map['team_a_score'] ?? null;
                    $mapScoreB = $map['team_b_score'] ?? null;

                    if ($mapScoreA === null || $mapScoreB === null) {
                        continue;
                    }

                    $myMapScore = $isTeamA ? $mapScoreA : $mapScoreB;
                    $theirMapScore = $isTeamA ? $mapScoreB : $mapScoreA;

                    if ($myMapScore > $theirMapScore) {
                        $mapWins++;
                    } elseif ($myMapScore < $theirMapScore) {
                        $mapLosses++;
                    }

                    $roundWins += $myMapScore;
                    $roundLosses += $theirMapScore;
                }
            }

            $winsByTeamId[$teamId] = $wins;
            $opponentIdsByTeamId[$teamId] = $opponentIds;

            $standings->push([
                'team' => $team,
                'wins' => $wins,
                'losses' => $losses,
                'match_diff' => $wins - $losses,
                'map_wins' => $mapWins,
                'map_losses' => $mapLosses,
                'map_diff' => $mapWins - $mapLosses,
                'round_wins' => $roundWins,
                'round_losses' => $roundLosses,
                'round_diff' => $roundWins - $roundLosses,
            ]);
        }

        $standings = $standings->map(function ($row) use ($winsByTeamId, $opponentIdsByTeamId) {
            $teamId = (string) $row['team']['id'];
            $row['buchholz'] = array_sum(array_map(
                fn ($opponentId) => $winsByTeamId[$opponentId] ?? 0,
                $opponentIdsByTeamId[$teamId] ?? []
            ));

            return $row;
        });

        return $standings->sortBy($useBuchholz ? [
            ['match_diff', 'desc'],
            ['buchholz', 'desc'],
            ['map_diff', 'desc'],
            ['round_diff', 'desc'],
        ] : [
            ['match_diff', 'desc'],
            ['map_diff', 'desc'],
            ['round_diff', 'desc'],
        ])->values();
    }
}
