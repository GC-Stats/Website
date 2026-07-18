<?php

/**
 * GC-Stats — Admin permission catalog
 *
 * The full, fixed set of permissions the admin dashboard actually checks
 * (one per action) — the single source of truth for both RoleSeeder and the
 * /admin/permissions matrix. Deliberately not user-extensible: a permission
 * created through the UI with a name nothing checks would just be dead
 * weight, so the matrix can only toggle these, grouped by section for
 * display.
 *
 * 'roles.manage' is intentionally excluded — it's gated by the
 * 'manage-roles' super-admin-only Gate instead (see AppServiceProvider),
 * since granting it as an assignable permission would let any role holder
 * assign themselves super-admin and escalate.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class AdminPermissions
{
    /**
     * @return array<string, list<string>> permission names grouped by section, for display
     */
    public static function grouped(): array
    {
        return [
            'reports' => ['reports.view', 'reports.resolve'],
            'sanctions' => ['sanctions.view', 'sanctions.create', 'sanctions.revoke'],
            'activity' => ['activity.account', 'activity.moderation'],
            'news' => ['news.manage'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::grouped()));
    }
}
