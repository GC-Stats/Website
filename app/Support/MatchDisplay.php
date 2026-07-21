<?php

/**
 * GC-Stats — Admin: match display helpers
 *
 * A team relation is null either because it hasn't been decided yet
 * (upcoming/live match) or because the match is finished and that side
 * was a bye — same for scheduled_at, which some imported matches carry
 * as a "1900-01-01" placeholder rather than a real null. Centralizes the
 * TBD/BYE/Unknown fallback so every admin matches view renders it the
 * same way.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use App\Models\Team;
use App\Models\TournamentPhase;
use Carbon\CarbonInterface;

class MatchDisplay
{
    public const UNKNOWN_DATE = '1900-01-01';

    public static function teamName(?Team $team, string $status): string
    {
        if ($team) {
            return $team->name;
        }

        return $status === 'finished' ? __('admin.matches.bye_team') : __('admin.matches.unknown_team');
    }

    public static function teamShortName(?Team $team, string $status): string
    {
        if ($team) {
            return $team->short_name ?: $team->name;
        }

        return $status === 'finished' ? __('admin.matches.bye_team') : __('admin.matches.unknown_team');
    }

    public static function scheduledAt(?CarbonInterface $date): string
    {
        if (! $date || $date->copy()->utc()->format('Y-m-d') === self::UNKNOWN_DATE) {
            return __('admin.matches.unknown_date');
        }

        return $date->format('Y-m-d H:i');
    }

    /**
     * A map's score of -1/-1 marks it as skipped entirely (see the
     * wikicode importer's "finished=skip" handling in MatchController);
     * a lone -1 on one side marks that side's forfeit instead.
     */
    public static function mapScore(?int $teamAScore, ?int $teamBScore): string
    {
        if (is_null($teamAScore) || is_null($teamBScore)) {
            return '—';
        }

        if ($teamAScore === -1 && $teamBScore === -1) {
            return __('admin.matches.maps.not_played');
        }

        $a = $teamAScore === -1 ? __('admin.matches.maps.forfeit') : $teamAScore;
        $b = $teamBScore === -1 ? __('admin.matches.maps.forfeit') : $teamBScore;

        return "{$a} - {$b}";
    }

    /**
     * Root phase gets no prefix; each level of nesting under a parent
     * phase adds one more "- " so a flat <select> still reads as a tree.
     * Walks $allPhases (a flat, already-loaded collection covering the
     * whole tournament) in memory instead of querying parent() per phase.
     */
    public static function phaseLabel(TournamentPhase $phase, iterable $allPhases): string
    {
        $byId = collect($allPhases)->keyBy('id');
        $depth = 0;
        $current = $phase;

        while ($current->parent_id && $byId->has($current->parent_id)) {
            $depth++;
            $current = $byId->get($current->parent_id);
        }

        return ($depth > 0 ? str_repeat('- ', $depth) : '').$phase->name;
    }
}
