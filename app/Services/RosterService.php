<?php

/**
 * GC-Stats — Roster service
 *
 * Shared bulk-save logic for the `player_team` pivot table, used by both
 * the team roster (history) and player team-history internal API endpoints
 * so behaviour stays identical regardless of which side initiates the edit.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RosterService
{
    public const ROLES = ['player', 'sub', 'manager', 'coach', 'assistant coach', 'analyst'];

    public function history(int $teamId): Collection
    {
        return DB::table('player_team')
            ->join('players', 'players.id', '=', 'player_team.player_id')
            ->where('player_team.team_id', $teamId)
            ->select('player_team.id', 'player_team.player_id', 'players.handle as player_handle', 'player_team.role', 'player_team.joined_at', 'player_team.left_at')
            ->orderByDesc('player_team.joined_at')
            ->get();
    }

    public function addMember(Team $team, int $playerId, ?string $role, string $joinedAt): void
    {
        $entries = $this->entriesFor($team->id);
        $entries[] = ['player_id' => $playerId, 'team_id' => $team->id, 'role' => $role ?: 'player', 'joined_at' => $joinedAt];

        $this->save('team_id', $team->id, $entries);
    }

    public function updateEntry(Team $team, int $entryId, array $fields): void
    {
        $entries = $this->entriesFor($team->id);

        foreach ($entries as &$entry) {
            if ($entry['id'] === $entryId) {
                $entry = array_merge($entry, $fields);
            }
        }

        $this->save('team_id', $team->id, $entries);
    }

    private function entriesFor(int $teamId): array
    {
        return DB::table('player_team')->where('team_id', $teamId)
            ->get(['id', 'player_id', 'team_id', 'role', 'joined_at', 'left_at'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Bulk-save `player_team` rows for a given player or team.
     *
     * Each entry in $entries may contain an `id` (existing pivot row to
     * update) or be a new row to insert. Any existing rows not present in
     * $entries (matched by id) are deleted.
     *
     * When a new row is inserted with no `left_at`, any other currently
     * active `player_team` row for that player (on a different team) is
     * closed out by setting its `left_at` to the new row's `joined_at`,
     * since a player shouldn't show as active on two rosters at once.
     *
     * @param  array<int, array{id?: int, player_id: int, team_id: int, role?: string, joined_at: string, left_at?: ?string}>  $entries
     */
    public function save(string $keyColumn, int $keyValue, array $entries): Collection
    {
        $affectedPlayerIds = [];
        $affectedTeamIds = [];

        $result = DB::transaction(function () use ($keyColumn, $keyValue, $entries, &$affectedPlayerIds, &$affectedTeamIds) {
            $existingIds = DB::table('player_team')->where($keyColumn, $keyValue)->pluck('id')->toArray();
            $keptIds = [];

            $rows = collect();
            $activeRows = [];

            foreach ($entries as $entry) {
                $data = [
                    'player_id' => $entry['player_id'],
                    'team_id' => $entry['team_id'],
                    'role' => $entry['role'] ?? 'player',
                    'joined_at' => $entry['joined_at'],
                    'left_at' => $entry['left_at'] ?? null,
                    'updated_at' => now(),
                ];

                if (! empty($entry['id'])) {
                    DB::table('player_team')->where('id', $entry['id'])->update($data);
                    $id = $entry['id'];
                } else {
                    $data['created_at'] = now();
                    $id = DB::table('player_team')->insertGetId($data);
                }
                $keptIds[] = $id;

                if ($data['left_at'] === null) {
                    $activeRows[] = ['id' => $id, 'player_id' => $data['player_id'], 'team_id' => $data['team_id'], 'joined_at' => $data['joined_at']];
                }

                $affectedPlayerIds[] = $data['player_id'];
                $affectedTeamIds[] = $data['team_id'];

                $rows->push(array_merge(['id' => $id], $data));
            }

            $activeIdsInBatch = array_column($activeRows, 'id');

            foreach ($activeRows as $activeRow) {
                $closedRows = DB::table('player_team')
                    ->where('player_id', $activeRow['player_id'])
                    ->where('team_id', '!=', $activeRow['team_id'])
                    ->whereNotIn('id', $activeIdsInBatch)
                    ->whereNull('left_at')
                    ->get(['id', 'team_id']);

                if ($closedRows->isNotEmpty()) {
                    DB::table('player_team')
                        ->whereIn('id', $closedRows->pluck('id'))
                        ->update(['left_at' => $activeRow['joined_at'], 'updated_at' => now()]);

                    foreach ($closedRows as $closedRow) {
                        $affectedTeamIds[] = $closedRow->team_id;
                    }
                }
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (! empty($toDelete)) {
                $deletedRows = DB::table('player_team')->whereIn('id', $toDelete)->get(['player_id', 'team_id']);
                foreach ($deletedRows as $row) {
                    $affectedPlayerIds[] = $row->player_id;
                    $affectedTeamIds[] = $row->team_id;
                }

                DB::table('player_team')->whereIn('id', $toDelete)->delete();
            }

            return $rows;
        });

        foreach (array_unique($affectedPlayerIds) as $playerId) {
            Cache::tags(["player_{$playerId}"])->flush();
        }

        foreach (array_unique($affectedTeamIds) as $teamId) {
            Cache::tags(["team_{$teamId}"])->flush();
        }

        return $result;
    }

    /**
     * Delete a single `player_team` row by id, scoped to $team, and flush
     * related caches. Scoping to $team prevents a caller authorized to
     * manage one team's roster from deleting another team's row by id.
     */
    public function deleteEntry(Team $team, int $id): bool
    {
        $row = DB::table('player_team')->where('id', $id)->where('team_id', $team->id)->first();

        if (! $row) {
            return false;
        }

        DB::table('player_team')->where('id', $id)->delete();

        Cache::tags(["player_{$row->player_id}"])->flush();
        Cache::tags(["team_{$row->team_id}"])->flush();

        return true;
    }
}
