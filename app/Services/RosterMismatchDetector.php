<?php

/**
 * GC-Stats — Roster mismatch detector
 *
 * Called from GameMapController::storeMatchData() right after a map's
 * player↔team-for-this-match assignments ($playerTeamMap) are computed from
 * observed API data. If a player is seen playing for a team that isn't
 * their current roster team (per player_team, left_at IS NULL), this
 * suggests our roster data is stale (a transfer we never recorded) — so it
 * opens a system-generated ChangeRequest proposing the roster update,
 * rather than silently trusting either source.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\GameMap;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Collection;

class RosterMismatchDetector
{
    public function __construct(private readonly ChangeRequestService $changeRequests) {}

    /**
     * @param  Collection<int, int>  $playerTeamMap  player_id => observed team_id, from this match's data.
     */
    public function detect(GameMap $map, Matchs $match, Collection $playerTeamMap): void
    {
        foreach ($playerTeamMap as $playerId => $observedTeamId) {
            $this->detectForPlayer($map, $match, (int) $playerId, (int) $observedTeamId);
        }
    }

    private function detectForPlayer(GameMap $map, Matchs $match, int $playerId, int $observedTeamId): void
    {
        $player = Player::find($playerId);

        if (! $player) {
            return;
        }

        $currentTeam = $player->teams()->wherePivot('left_at', null)->first();

        if ($currentTeam?->id === $observedTeamId) {
            return;
        }

        if ($this->hasPendingRosterRequest($playerId, $observedTeamId)) {
            return;
        }

        $observedTeam = Team::find($observedTeamId);

        if (! $observedTeam) {
            return;
        }

        $joinedAt = $match->scheduled_at?->toDateString() ?? now()->toDateString();

        $reason = sprintf(
            "Detected while fetching %s on %s: %s played for %s in this match, but their current roster team is %s.\nSuggested change: move %s to %s as of %s.",
            $map->map_name ?? 'a map',
            $match->tournament?->name ?? 'a tournament',
            $player->handle,
            $observedTeam->name,
            $currentTeam?->name ?? 'no team',
            $player->handle,
            $observedTeam->name,
            $joinedAt,
        );

        $this->changeRequests->create($player, null, $reason, [[
            'field' => 'roster',
            'old_value' => $currentTeam ? ['team_id' => $currentTeam->id, 'team_name' => $currentTeam->name] : null,
            'new_value' => ['team_id' => $observedTeam->id, 'team_name' => $observedTeam->name, 'role' => 'player', 'joined_at' => $joinedAt],
        ]]);
    }

    private function hasPendingRosterRequest(int $playerId, int $observedTeamId): bool
    {
        return ChangeRequest::query()
            ->where('subject_type', 'player')
            ->where('subject_id', $playerId)
            ->pending()
            ->whereHas('items', fn ($query) => $query
                ->where('field', 'roster')
                ->where('status', ChangeRequestItem::STATUS_PENDING)
                ->whereJsonContains('new_value->team_id', $observedTeamId))
            ->exists();
    }
}
