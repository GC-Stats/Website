<?php

/**
 * GC-Stats — Team role service
 *
 * Lazily provisions the per-team roles (team_owner, team_manager,
 * team_editor) the first time a team actually needs one, rather than
 * pre-seeding three role rows for every one of the already-imported teams.
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

    private const ROLES = [self::ROLE_OWNER, self::ROLE_MANAGER, self::ROLE_EDITOR];

    public function ensureRolesExist(Team $team): void
    {
        PermissionTeam::use($team->id);

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role);
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
