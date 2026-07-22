<?php

/**
 * GC-Stats — Qualification result resolver
 *
 * Keeps PhaseQualificationResult continuously in sync with whichever
 * team(s) actually satisfy a PhaseQualification rule right now — recomputed
 * (not a one-time snapshot) whenever a relevant match or rule is saved, via
 * MatchObserver/PhaseQualificationObserver. A team result also fans out into
 * one PhaseQualificationResult row per player who was on that team's roster
 * for the tournament, derived from GamePlayerStat (actual match/map
 * participation, which correctly reflects mid-tournament roster changes
 * unlike the team's current join/leave-dated roster pivot). If the rule
 * awards points and the tournament is bound to a point type, the team
 * result also gets a matching PointEntry ledger row (see syncPointEntry) —
 * points are team-only, never fanned out to players.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\PhaseQualification;
use App\Models\PhaseQualificationResult;
use App\Models\PointEntry;
use App\Models\TournamentPhase;
use App\Support\TournamentStandings;

class PhaseQualificationResolver
{
    /**
     * Resolve every match-outcome rule (bracket) attached to this match.
     */
    public function resolveForMatch(int $matchId): void
    {
        $rules = PhaseQualification::where('source_match_id', $matchId)->get();

        if ($rules->isEmpty()) {
            return;
        }

        $match = Matchs::find($matchId);

        $scoresKnown = $match
            && $match->status === 'finished'
            && ! is_null($match->team_a_score)
            && ! is_null($match->team_b_score)
            && $match->team_a_score !== $match->team_b_score;

        foreach ($rules as $rule) {
            if (! $scoresKnown) {
                $this->syncRuleEntities($rule, []);

                continue;
            }

            $winnerIsA = $match->team_a_score > $match->team_b_score;
            $teamId = $rule->outcome === 'winner'
                ? ($winnerIsA ? $match->team_a_id : $match->team_b_id)
                : ($winnerIsA ? $match->team_b_id : $match->team_a_id);

            $this->syncRuleEntities($rule, $teamId ? [$teamId => null] : []);
        }
    }

    /**
     * Recompute every rank-based rule (swiss/round_robin) sourced from this phase.
     */
    public function resolveForPhase(int $phaseId): void
    {
        $rules = PhaseQualification::where('source_phase_id', $phaseId)->whereNull('source_match_id')->get();

        if ($rules->isEmpty()) {
            return;
        }

        $matches = Matchs::where('phase_id', $phaseId)->with('game_maps')->get()->map(fn ($m) => [
            'team_a_id' => $m->team_a_id,
            'team_b_id' => $m->team_b_id,
            'team_a_score' => $m->team_a_score,
            'team_b_score' => $m->team_b_score,
            'game_maps' => $m->game_maps->map(fn ($gm) => [
                'team_a_score' => $gm->team_a_score,
                'team_b_score' => $gm->team_b_score,
            ])->all(),
        ])->all();

        $teamIds = collect($matches)->flatMap(fn ($m) => [$m['team_a_id'], $m['team_b_id']])->filter()->unique()->values();
        $teams = $teamIds->map(fn ($id) => ['id' => $id])->all();

        $standings = TournamentStandings::compute($matches, $teams)->values();

        foreach ($rules as $rule) {
            $teamsWithRank = [];

            for ($rank = $rule->rank_from; $rank <= $rule->rank_to; $rank++) {
                $row = $standings->get($rank - 1);

                if ($row) {
                    $teamsWithRank[$row['team']['id']] = $rank;
                }
            }

            $this->syncRuleEntities($rule, $teamsWithRank);
        }
    }

    /**
     * Replace this rule's resolved entities (team + its tournament roster's players) with exactly
     * the given set, adding/updating what's now true and deleting whatever no longer is.
     *
     * @param  array<int, int|null>  $teamIdsWithRank  [team_id => rank|null]
     */
    private function syncRuleEntities(PhaseQualification $rule, array $teamIdsWithRank): void
    {
        $phase = TournamentPhase::with('tournament:id,point_type_id')->find($rule->source_phase_id);
        $tournamentId = $phase?->tournament_id;
        $pointTypeId = $phase?->tournament?->point_type_id;
        $validKeys = [];

        foreach ($teamIdsWithRank as $teamId => $rank) {
            $teamResult = PhaseQualificationResult::updateOrCreate(
                ['phase_qualification_id' => $rule->id, 'entity_type' => 'team', 'entity_id' => $teamId],
                ['rank' => $rank]
            );
            $validKeys[] = "team:{$teamId}";

            $this->syncPointEntry($teamResult, $rule, $teamId, $pointTypeId);

            if (! $tournamentId) {
                continue;
            }

            $playerIds = GamePlayerStat::where('tournament_id', $tournamentId)
                ->where('team_id', $teamId)
                ->distinct()
                ->pluck('player_id');

            foreach ($playerIds as $playerId) {
                PhaseQualificationResult::updateOrCreate(
                    ['phase_qualification_id' => $rule->id, 'entity_type' => 'player', 'entity_id' => $playerId],
                    ['rank' => $rank]
                );
                $validKeys[] = "player:{$playerId}";
            }
        }

        // Stale results (team dropped out of the range, or the rule/standings changed)
        // cascade-delete their point_entries row at the DB level — no separate cleanup needed.
        PhaseQualificationResult::where('phase_qualification_id', $rule->id)
            ->get()
            ->each(function ($result) use ($validKeys) {
                if (! in_array("{$result->entity_type}:{$result->entity_id}", $validKeys, true)) {
                    $result->delete();
                }
            });
    }

    /**
     * A team result only produces a ledger entry when its rule awards points AND the
     * tournament is bound to a point type — otherwise any existing entry is removed
     * (e.g. the rule's points were cleared while the team still qualifies).
     */
    private function syncPointEntry(PhaseQualificationResult $teamResult, PhaseQualification $rule, int $teamId, ?int $pointTypeId): void
    {
        if (! $rule->points || ! $pointTypeId) {
            $teamResult->pointEntry?->delete();

            return;
        }

        PointEntry::updateOrCreate(
            ['phase_qualification_result_id' => $teamResult->id],
            ['team_id' => $teamId, 'point_type_id' => $pointTypeId, 'amount' => $rule->points]
        );
    }
}
