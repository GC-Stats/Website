<?php

/**
 * GC-Stats — Staff/team roster service
 *
 * Bulk-save logic for the `staff_teams` pivot table — a staff member working
 * directly for a team, with no organization in between (see StaffTeam's
 * docblock, and Staff's original design: "a team has no organization, staff
 * link directly to the team instead"). Parallel to
 * App\Services\StaffOrganizationService (save() is shared via
 * StaffRosterService): no sibling active row is ever closed out, since a
 * staff member may work directly with several teams at once.
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
use Illuminate\Support\Facades\DB;

class StaffTeamService extends StaffRosterService
{
    public const ROLES = StaffRoles::TEAM_ROLES;

    protected function table(): string
    {
        return 'staff_teams';
    }

    protected function foreignKey(): string
    {
        return 'team_id';
    }

    protected function cacheTagPrefix(): string
    {
        return 'team';
    }

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
}
