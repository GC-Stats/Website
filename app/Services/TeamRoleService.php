<?php

/**
 * GC-Stats — Team role service
 *
 * Lazily provisions the per-team roles (team_owner, team_manager,
 * team_editor) the first time a team actually needs one, with a starting
 * set of permissions capped to the team's own max_permissions ceiling
 * (set by a site admin, see Admin\TeamController) — empty/unset means the
 * team starts with no self-management access at all. Fully editable
 * afterward via Team\RoleController, independently per team.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Support\PermissionTeam;
use Spatie\Permission\Models\Role;

class TeamRoleService
{
    public const ROLE_OWNER = 'team_owner';

    public const ROLE_MANAGER = 'team_manager';

    public const ROLE_EDITOR = 'team_editor';

    private const ROLE_COUNT = 3;

    public function ensureRolesExist(Team $team): void
    {
        // Cheap bail-out: once all three roles exist (the overwhelming
        // majority of calls — this runs on every team roles page view,
        // not just on first provisioning) skip straight past the
        // per-role existence checks below with a single query.
        if (Role::where('team_id', $team->id)->count() >= self::ROLE_COUNT) {
            return;
        }

        PermissionTeam::use($team->id);

        $ceiling = $team->maxPermissions();

        $defaults = [
            self::ROLE_OWNER => $ceiling, // starts at the team's full ceiling; site admins can restrict per role from there
            self::ROLE_MANAGER => array_intersect(['team.profile.edit', 'team.roster.manage'], $ceiling),
            self::ROLE_EDITOR => array_intersect(['team.roster.manage'], $ceiling),
        ];

        foreach ($defaults as $role => $permissions) {
            // team_id must be explicit here: spatie's Role model does not
            // apply an automatic team-scoped query scope on plain
            // where('name', ...) lookups (only on the creating hook and
            // the HasRoles::roles() relation) — without this filter, one
            // team having 'team_owner' makes every other team's
            // ensureRolesExist() wrongly believe its own row already
            // exists and skip creating it.
            if (Role::where('name', $role)->where('team_id', $team->id)->exists()) {
                continue;
            }

            Role::create(['name' => $role])->syncPermissions(array_values($permissions));
        }
    }

    public function assign(User $user, Team $team, string $role): void
    {
        $this->ensureRolesExist($team);

        PermissionTeam::use($team->id);
        $user->assignRole($role);
    }

    public function revoke(User $user, Team $team, string $role): void
    {
        PermissionTeam::use($team->id);
        $user->removeRole($role);
    }
}
