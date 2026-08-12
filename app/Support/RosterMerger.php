<?php

/**
 * GC-Stats — Roster stint merger
 *
 * Collapses chronologically adjacent `player_team` rows for the same
 * player+team pair into a single visual "stint" when they share a base
 * role (e.g. 'player' immediately followed by 'player-inactive', with no
 * gap between the first row's left_at and the second row's joined_at).
 *
 * A player going inactive is stored as a new pivot row rather than a flag
 * on the existing one, which otherwise makes an uninterrupted membership
 * look like two separate stints on the roster/history pages. Merging here
 * lets the display stay a single entry spanning the full period, with the
 * inactive spell surfaced as metadata instead of a second card.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use App\Helpers\RosterRole;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RosterMerger
{
    /**
     * @param  Collection  $rows  Eloquent models exposing ->pivot->{role, joined_at, left_at, player_id, team_id}
     * @param  string  $groupBy  Pivot column identifying which stints belong together: 'player_id' when
     *                           merging a team's roster (grouping per player), 'team_id' when merging a
     *                           player's team history (grouping per team).
     * @return Collection<int, array{model: mixed, role: string, joined_at: ?string, left_at: ?string, inactive_since: ?string}>
     */
    public static function merge(Collection $rows, string $groupBy): Collection
    {
        return $rows
            ->groupBy(fn ($row) => $row->pivot->{$groupBy})
            ->flatMap(fn (Collection $group) => self::mergeGroup($group))
            ->values();
    }

    private static function mergeGroup(Collection $group): array
    {
        $ordered = $group->sortBy(fn ($row) => $row->pivot->joined_at)->values();

        $stints = [];
        $segment = [];

        foreach ($ordered as $row) {
            $last = $segment ? end($segment) : null;

            $contiguous = $last
                && RosterRole::baseRole($last->pivot->role) === RosterRole::baseRole($row->pivot->role)
                && self::sameInstant($last->pivot->left_at, $row->pivot->joined_at);

            if (! $contiguous && $segment) {
                $stints[] = self::buildStint($segment);
                $segment = [];
            }

            $segment[] = $row;
        }

        if ($segment) {
            $stints[] = self::buildStint($segment);
        }

        return $stints;
    }

    private static function sameInstant(?string $a, ?string $b): bool
    {
        return $a !== null && $b !== null && Carbon::parse($a)->equalTo(Carbon::parse($b));
    }

    private static function buildStint(array $segment): array
    {
        $first = $segment[0];
        $last = end($segment);

        $hasInactive = collect($segment)->contains(fn ($row) => RosterRole::isInactive($row->pivot->role));
        $hasActive = collect($segment)->contains(fn ($row) => ! RosterRole::isInactive($row->pivot->role));
        $isMixed = $hasInactive && $hasActive;
        $isOngoing = $last->pivot->left_at === null;

        // History stints (closed) always read by their active role, with the inactive spell called
        // out as text. Ongoing stints keep showing whatever the current row's role actually is.
        $role = ($isMixed && ! $isOngoing) ? RosterRole::baseRole($last->pivot->role) : $last->pivot->role;

        $inactiveSince = null;
        if ($isMixed && (! $isOngoing || RosterRole::isInactive($last->pivot->role))) {
            $inactiveSince = collect($segment)
                ->first(fn ($row) => RosterRole::isInactive($row->pivot->role))
                ->pivot->joined_at;
        }

        return [
            'model' => $last,
            'role' => $role,
            'joined_at' => $first->pivot->joined_at,
            'left_at' => $last->pivot->left_at,
            'inactive_since' => $inactiveSince,
        ];
    }
}
