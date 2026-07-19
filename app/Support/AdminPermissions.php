<?php

/**
 * GC-Stats — Admin permission catalog
 *
 * Fixed set of permissions the admin dashboard actually checks, one per
 * action — source of truth for RoleSeeder and the roles permission matrix.
 * Not user-extensible. Role/permission management itself is deliberately
 * excluded — gated by the super-admin-only 'manage-roles' Gate instead, so
 * no role can grant itself the means to escalate.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class AdminPermissions extends PermissionCatalog
{
    /**
     * @return array<string, list<string>> permission names grouped by section, for display
     */
    public static function grouped(): array
    {
        return [
            'reports' => ['reports.view', 'reports.resolve'],
            'sanctions' => ['sanctions.view', 'sanctions.create', 'sanctions.revoke', 'sanctions.delete'],
            'activity' => ['activity.account', 'activity.moderation', 'activity.administration', 'activity.team', 'activity.player'],
            'teams' => ['teams.view', 'teams.edit', 'teams.delete', 'teams.merge'],
            'players' => ['players.view', 'players.edit', 'players.delete', 'players.merge', 'players.identifiers.manage'],
            'news' => ['news.manage'],
        ];
    }
}
