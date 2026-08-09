<?php

/**
 * GC-Stats — Staff role label
 *
 * Translates a raw staff role value (App\Support\StaffRoles::TEAM_ROLES or
 * ::ORG_ROLES) into its localized label from lang/*\/staff.php's
 * `roster.roles.team`/`roster.roles.org` maps, falling back to the raw value
 * for anything not in either map. Mirrors App\Helpers\RosterRole, minus the
 * player-specific igl/sub/inactive grouping — staff roles only need a
 * team-vs-org distinction for display accent purposes, driven by
 * StaffRoles::TEAM_ROLES membership rather than a hardcoded broadcast list.
 *
 * Some French role labels carry grammatical gender ("Président"/"Présidente")
 * — those lang entries are a [Pronouns::FEMININE => ..., ...] array instead
 * of a plain string, resolved via App\Support\Pronouns::agree() the same way
 * every other gendered label on the site is (see Pronouns' docblock).
 * $pronouns should be the staff member's own `pronouns` column when known;
 * null falls back to the site's feminine default.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Helpers;

use App\Support\Pronouns;
use App\Support\StaffRoles;

class StaffRoleLabel
{
    private const STYLES = [
        'team' => ['bar' => 'bg-purple-400', 'badge' => 'bg-purple-400/10 text-purple-300'],
        'org' => ['bar' => 'bg-sky-400', 'badge' => 'bg-sky-400/10 text-sky-300'],
    ];

    public static function label(?string $role, ?int $pronouns = null): ?string
    {
        if (! $role) {
            return $role;
        }

        $key = self::langKey($role);

        return $key ? Pronouns::trans($key, $pronouns) : $role;
    }

    /**
     * Role picker options (e.g. for <x-styled-select>) — always the neutral/
     * inclusive form ("Président·e"), never a specific person's gendered
     * label, since a dropdown of role choices isn't bound to any one
     * person's pronouns. Plain (non-gendered) role strings pass through
     * unchanged.
     *
     * @param  list<string>  $roles  StaffRoles::TEAM_ROLES or ::ORG_ROLES
     * @return array<string, string>
     */
    public static function options(array $roles): array
    {
        $options = [];
        foreach ($roles as $role) {
            $key = self::langKey($role);
            $options[$role] = $key ? Pronouns::trans($key, Pronouns::NEUTRAL) : $role;
        }

        return $options;
    }

    /** The lang key for $role's entry in staff.roster.roles.{team,org}, or null if it's in neither map. */
    private static function langKey(string $role): ?string
    {
        $roles = __('staff.roster.roles');

        return match (true) {
            isset($roles['team'][$role]) => "staff.roster.roles.team.{$role}",
            isset($roles['org'][$role]) => "staff.roster.roles.org.{$role}",
            default => null,
        };
    }

    public static function group(?string $role): string
    {
        return in_array($role, StaffRoles::TEAM_ROLES, true) ? 'team' : 'org';
    }

    public static function barClass(?string $role): string
    {
        return self::STYLES[self::group($role)]['bar'];
    }

    public static function badgeClass(?string $role): string
    {
        return self::STYLES[self::group($role)]['badge'];
    }
}
