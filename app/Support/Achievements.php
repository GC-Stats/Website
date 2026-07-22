<?php

/**
 * GC-Stats — Achievements (top-3 placements) for a team or player
 *
 * Reads PhaseQualificationResult rows already resolved by
 * PhaseQualificationResolver — only "final placement" rules (destination_type
 * = placement) count as an achievement here; a rule that just advances a
 * team to another phase isn't a result to show on a profile.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Str;

class Achievements
{
    /**
     * @return array<int, array{tier: string, label: string, tournament_name: string, tournament_url: string}>
     */
    public static function forEntity(Team|Player $entity, int $limit = 6): array
    {
        return $entity->qualificationResults()
            ->whereHas('phaseQualification', fn ($q) => $q->where('destination_type', 'placement')->where('placement', '<=', 3))
            ->with('phaseQualification.sourcePhase.tournament')
            ->get()
            ->map(function ($result) {
                $rule = $result->phaseQualification;
                $tournament = $rule->sourcePhase?->tournament;

                if (! $tournament) {
                    return null;
                }

                return [
                    'tier' => match ($rule->placement) {
                        1 => 'gold',
                        2 => 'silver',
                        default => 'bronze',
                    },
                    'label' => $rule->placement_label ?: match ($rule->placement) {
                        1 => '1st', 2 => '2nd', 3 => '3rd', default => "{$rule->placement}th",
                    },
                    'tournament_name' => $tournament->name,
                    'tournament_logo' => $tournament->logo,
                    'tournament_url' => route('tournaments.show', [$tournament->id, Str::routeSlug($tournament->name, $tournament->id)]),
                    'sort_date' => $tournament->end_date ?? $tournament->start_date,
                ];
            })
            ->filter()
            ->sortByDesc('sort_date')
            ->take($limit)
            ->values()
            ->map(fn ($achievement) => collect($achievement)->except('sort_date')->all())
            ->all();
    }
}
