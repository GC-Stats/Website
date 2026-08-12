<?php

/**
 * GC-Stats — Roster role label
 *
 * Translates a raw `player_team.role` value (App\Services\RosterService::ROLES)
 * into its localized label from lang/*\/team.php's `roster.roles` map, falling
 * back to the raw value for anything not in that map (e.g. legacy data).
 *
 * Also buckets roles into broad groups (player / sub / staff) purely for
 * presentation (accent colors, grouping) — this is not translated, so it
 * doesn't need to touch lang files.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Helpers;

use App\Services\RosterService;

class RosterRole
{
    private const GROUPS = [
        'igl' => ['player-igl'],
        'player' => ['player'],
        'sub' => ['sub'],
        'manager' => ['manager'],
    ];

    /** Accent color per group, shared by every roster/history listing. */
    private const STYLES = [
        'igl' => ['bar' => 'bg-[var(--brand-yellow)]', 'badge' => 'bg-purple-400/10 text-purple-300'],
        'player' => ['bar' => 'bg-[var(--brand-yellow)]', 'badge' => 'bg-[var(--brand-yellow)]/10 text-[var(--brand-yellow)]'],
        'sub' => ['bar' => 'bg-sky-400', 'badge' => 'bg-sky-400/10 text-sky-300'],
        'staff' => ['bar' => 'bg-purple-400', 'badge' => 'bg-purple-400/10 text-purple-300'],
        'manager' => ['bar' => 'bg-orange-400', 'badge' => 'bg-orange-400/10 text-orange-300'],
        'inactive' => ['bar' => 'bg-gray-500', 'badge' => 'bg-gray-500/10 text-gray-400'],
    ];

    public static function label(?string $role): ?string
    {
        if (! $role) {
            return $role;
        }

        return __('team.roster.roles')[$role] ?? $role;
    }

    public static function isInactive(?string $role): bool
    {
        return $role !== null && str_ends_with($role, '-inactive');
    }

    /** Strips the '-inactive' suffix, e.g. 'player-inactive' -> 'player'. Roles without it pass through unchanged. */
    public static function baseRole(?string $role): ?string
    {
        return self::isInactive($role) ? substr($role, 0, -strlen('-inactive')) : $role;
    }

    /**
     * Buckets a raw role into 'igl', 'player', 'sub', 'manager' or 'staff'
     * for styling purposes (accent color, grouping spacing) — everything
     * not a recognized player/sub/manager role (coach, analyst, ...) is
     * staff. Any '-inactive' variant is bucketed into 'inactive' instead,
     * regardless of its active role, so retired members always read as a
     * single neutral color.
     */
    public static function group(?string $role): string
    {
        if ($role && str_ends_with($role, '-inactive')) {
            return 'inactive';
        }

        foreach (self::GROUPS as $group => $roles) {
            if (in_array($role, $roles, true)) {
                return $group;
            }
        }

        return 'staff';
    }

    /**
     * Canonical roster display order: playerIGL/player/sub/coach/
     * assistant coach/performance coach/analyst/manager, then the same
     * order again for the inactive variants — mirrors RosterService::ROLES.
     */
    public static function sortIndex(?string $role): int
    {
        $index = array_search($role, RosterService::ROLES, true);

        return $index === false ? count(RosterService::ROLES) : $index;
    }

    /** Tailwind classes for the colored accent bar on a roster/history entry card. */
    public static function barClass(?string $role): string
    {
        return self::STYLES[self::group($role)]['bar'];
    }

    /** Tailwind classes for the colored role badge on a roster/history entry card. */
    public static function badgeClass(?string $role): string
    {
        return self::STYLES[self::group($role)]['badge'];
    }
}
