<?php

/**
 * GC-Stats — Agent role helper
 *
 * Resolves a Valorant agent's role from its display name, and the light
 * shadow color used to badge its icon by role on the maps pages.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Helpers;

class AgentRoles
{
    private const COLORS = [
        'duelist' => 'rgba(248,113,113,0.22)',
        'initiator' => 'rgba(251,146,60,0.22)',
        'controller' => 'rgba(192,132,252,0.22)',
        'sentinel' => 'rgba(52,211,153,0.22)',
    ];

    public static function slug(string $agentName): string
    {
        return strtolower(str_replace('/', '', $agentName));
    }

    public static function roleFor(string $agentName): ?string
    {
        $slug = self::slug($agentName);

        foreach (config('agent_roles', []) as $role => $slugs) {
            if (in_array($slug, $slugs, true)) {
                return $role;
            }
        }

        return null;
    }

    public static function shadowColorFor(string $agentName): ?string
    {
        $role = self::roleFor($agentName);

        return $role ? self::COLORS[$role] : null;
    }
}
