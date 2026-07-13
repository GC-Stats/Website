<?php

/**
 * GC-Stats — Match presentation helper
 *
 * Shared formatter used by PlayerController (index/matches) and
 * TeamController (matches) to turn a Matchs model into the left/right team
 * perspective array consumed by the player.* and team.* Blade views:
 * whichever side the reference entity (player's team, or team itself) was
 * on becomes "team_a" (left) and the opponent becomes "team_b" (right), and
 * a win/loss/draw "result" is derived from the (now perspective-relative)
 * scores.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use App\Models\Matchs;

class MatchPresenter
{
    /**
     * Format a match from the perspective of $referenceTeamId (the team ID
     * to place on the "left"/"team_a" side of the result — the player's
     * current team for that match, or the team whose page is being shown).
     */
    public static function format(Matchs $match, $referenceTeamId): array
    {
        $isReferenceTeamA = $match->team_a_id == $referenceTeamId;

        $leftTeam = $isReferenceTeamA ? $match->teamA : $match->teamB;
        $rightTeam = $isReferenceTeamA ? $match->teamB : $match->teamA;

        $leftScore = $isReferenceTeamA ? $match->team_a_score : $match->team_b_score;
        $rightScore = $isReferenceTeamA ? $match->team_b_score : $match->team_a_score;

        $result = 'draw';
        if ($match->status === 'finished') {
            if ($leftScore > $rightScore) {
                $result = 'win';
            } elseif ($leftScore < $rightScore) {
                $result = 'loss';
            }
        }

        return [
            'id' => $match->id,
            'status' => $match->status,
            'round_name' => $match->round_name,
            'scheduled_at' => $match->scheduled_at?->toDateTimeString(),
            'tournament_name' => $match->tournament->name ?? null,
            'phase_name' => $match->tournamentPhase->name ?? null,
            'team_a' => $leftTeam ? [
                'id' => $leftTeam->id,
                'name' => $leftTeam->name,
                'logo' => $leftTeam->logo,
            ] : null,
            'team_b' => $rightTeam ? [
                'id' => $rightTeam->id,
                'name' => $rightTeam->name,
                'logo' => $rightTeam->logo,
            ] : null,
            'team_a_score' => (int) $leftScore,
            'team_b_score' => (int) $rightScore,
            'result' => $result,
        ];
    }
}
