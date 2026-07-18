<?php

/**
 * GC-Stats — Team permission catalog
 *
 * Fixed set of permissions a team's own roles can be granted, defined once
 * here by site admins and shared globally (spatie's `permissions` table
 * isn't team-scoped — only `roles` is, so the same permission row is
 * reused across every team's role_has_permissions). Each team's roles
 * (team_owner/manager/editor, or custom ones) get their own independent
 * subset via App\Http\Controllers\Team\RoleController — one team granting
 * team_owner everything doesn't affect another team's team_owner.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class TeamPermissions extends PermissionCatalog
{
    /**
     * @return array<string, list<string>> permission names grouped by section, for display
     */
    public static function grouped(): array
    {
        return [
            'profile' => ['team.profile.edit', 'team.logo.upload'],
            'roster' => ['team.roster.manage'],
            'roles' => ['team.roles.manage'],
        ];
    }

    /**
     * grouped(), narrowed to only the permissions in $ceiling (a team's
     * own max_permissions) — groups left with nothing in them are dropped.
     *
     * @param  list<string>  $ceiling
     * @return array<string, list<string>>
     */
    public static function groupedWithin(array $ceiling): array
    {
        return collect(self::grouped())
            ->map(fn ($permissions) => array_values(array_intersect($permissions, $ceiling)))
            ->filter(fn ($permissions) => $permissions !== [])
            ->all();
    }
}
