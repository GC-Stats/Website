<?php

/**
 * GC-Stats — Staff/team roster service
 *
 * Shared bulk-save logic for the `staff_teams` pivot table — a staff member
 * working directly for a team, with no organization in between (see
 * StaffTeam's docblock, and Staff's original design: "a team has no
 * organization, staff link directly to the team instead"). Parallel to
 * App\Services\StaffOrganizationService: no sibling active row is ever
 * closed out, since a staff member may work directly with several teams
 * at once.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Team;
use App\Support\StaffRoles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StaffTeamService
{
    public const ROLES = StaffRoles::TEAM_ROLES;

    public function history(int $teamId): Collection
    {
        return DB::table('staff_teams')
            ->join('staff', 'staff.id', '=', 'staff_teams.staff_id')
            ->where('staff_teams.team_id', $teamId)
            ->select('staff_teams.id', 'staff_teams.staff_id', 'staff.handle as staff_handle', 'staff_teams.role', 'staff_teams.joined_at', 'staff_teams.left_at')
            ->orderByDesc('staff_teams.joined_at')
            ->get();
    }

    /**
     * The reverse of history() — every `staff_teams` row for a given staff
     * member, for the admin staff page's editable team-affiliation panel.
     */
    public function teamHistory(int $staffId): Collection
    {
        return DB::table('staff_teams')
            ->join('teams', 'teams.id', '=', 'staff_teams.team_id')
            ->where('staff_teams.staff_id', $staffId)
            ->select('staff_teams.id', 'staff_teams.team_id', 'teams.name as team_name', 'staff_teams.role', 'staff_teams.joined_at', 'staff_teams.left_at')
            ->orderByDesc('staff_teams.joined_at')
            ->get();
    }

    public function addMember(Team $team, int $staffId, ?string $role, string $joinedAt): void
    {
        $entries = $this->entriesFor($team->id);
        $entries[] = ['staff_id' => $staffId, 'team_id' => $team->id, 'role' => $role ?: self::ROLES[0], 'joined_at' => $joinedAt];

        $this->save('team_id', $team->id, $entries);
    }

    private function entriesFor(int $teamId): array
    {
        return DB::table('staff_teams')->where('team_id', $teamId)
            ->get(['id', 'staff_id', 'team_id', 'role', 'joined_at', 'left_at'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Bulk-save `staff_teams` rows for a given staff member or team. Same
     * shape as StaffOrganizationService::save() — no sibling active row is
     * ever closed out.
     *
     * @param  array<int, array{id?: int, staff_id: int, team_id: int, role?: string, joined_at: string, left_at?: ?string}>  $entries
     */
    public function save(string $keyColumn, int $keyValue, array $entries): Collection
    {
        $affectedStaffIds = [];
        $affectedTeamIds = [];

        $result = DB::transaction(function () use ($keyColumn, $keyValue, $entries, &$affectedStaffIds, &$affectedTeamIds) {
            $existingIds = DB::table('staff_teams')->where($keyColumn, $keyValue)->pluck('id')->toArray();
            $keptIds = [];

            $rows = collect();

            foreach ($entries as $entry) {
                $data = [
                    'staff_id' => $entry['staff_id'],
                    'team_id' => $entry['team_id'],
                    'role' => $entry['role'] ?? self::ROLES[0],
                    'joined_at' => $entry['joined_at'],
                    'left_at' => $entry['left_at'] ?? null,
                    'updated_at' => now(),
                ];

                if (! empty($entry['id'])) {
                    DB::table('staff_teams')->where('id', $entry['id'])->update($data);
                    $id = $entry['id'];
                } else {
                    $data['created_at'] = now();
                    $id = DB::table('staff_teams')->insertGetId($data);
                }
                $keptIds[] = $id;

                $affectedStaffIds[] = $data['staff_id'];
                $affectedTeamIds[] = $data['team_id'];

                $rows->push(array_merge(['id' => $id], $data));
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (! empty($toDelete)) {
                $deletedRows = DB::table('staff_teams')->whereIn('id', $toDelete)->get(['staff_id', 'team_id']);
                foreach ($deletedRows as $row) {
                    $affectedStaffIds[] = $row->staff_id;
                    $affectedTeamIds[] = $row->team_id;
                }

                DB::table('staff_teams')->whereIn('id', $toDelete)->delete();
            }

            return $rows;
        });

        foreach (array_unique($affectedStaffIds) as $staffId) {
            Cache::tags(["staff_{$staffId}"])->flush();
        }

        foreach (array_unique($affectedTeamIds) as $teamId) {
            Cache::tags(["team_{$teamId}"])->flush();
        }

        return $result;
    }
}
