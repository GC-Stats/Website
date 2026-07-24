<?php

/**
 * GC-Stats — Roster role label
 *
 * Translates a raw `player_team.role` value (App\Services\RosterService::ROLES)
 * into its localized label from lang/*\/team.php's `roster.roles` map, falling
 * back to the raw value for anything not in that map (e.g. legacy data).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Helpers;

class RosterRole
{
    public static function label(?string $role): ?string
    {
        if (! $role) {
            return $role;
        }

        return __('team.roster.roles')[$role] ?? $role;
    }
}
