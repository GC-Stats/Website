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

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RosterService
{
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
                    $keptIds[] = $id;
                } else {
                    $data['created_at'] = now();
                    $id = DB::table('player_team')->insertGetId($data);
                    $keptIds[] = $id;

                    if ($data['left_at'] === null) {
                        $closedRows = DB::table('player_team')
                            ->where('player_id', $data['player_id'])
                            ->where('team_id', '!=', $data['team_id'])
                            ->where('id', '!=', $id)
                            ->whereNull('left_at')
                            ->get(['id', 'team_id']);

                        if ($closedRows->isNotEmpty()) {
                            DB::table('player_team')
                                ->whereIn('id', $closedRows->pluck('id'))
                                ->update(['left_at' => $data['joined_at'], 'updated_at' => now()]);

                            foreach ($closedRows as $closedRow) {
                                $affectedTeamIds[] = $closedRow->team_id;
                            }
                        }
                    }
                }

                $affectedPlayerIds[] = $data['player_id'];
                $affectedTeamIds[] = $data['team_id'];

                $rows->push(array_merge(['id' => $id], $data));
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
     * Delete a single `player_team` row by id and flush related caches.
     */
    public function deleteEntry(int $id): bool
    {
        $row = DB::table('player_team')->where('id', $id)->first();

        if (! $row) {
            return false;
        }

        DB::table('player_team')->where('id', $id)->delete();

        Cache::tags(["player_{$row->player_id}"])->flush();
        Cache::tags(["team_{$row->team_id}"])->flush();

        return true;
    }
}
