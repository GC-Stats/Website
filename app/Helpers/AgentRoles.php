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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AgentRoles
{
    private const ABILITY_SLOT_MAP = [
        'Ability1' => 'Ability1',
        'Ability2' => 'Ability2',
        'Grenade' => 'GrenadeAbility',
        'Ultimate' => 'Ultimate',
    ];

    private const ABILITY_CACHE_KEY = 'agent_abilities_map';

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

    /**
     * Flattens the agent slugs for a set of roles (used to scope stats
     * queries by role via a slugified agent_name comparison).
     *
     * @param  list<string>  $roles
     * @return list<string>
     */
    public static function slugsForRoles(array $roles): array
    {
        return collect($roles)
            ->flatMap(fn ($role) => config("agent_roles.{$role}", []))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve an agent's display name for one of its kill-feed ability
     * slots (Ability1, Ability2, GrenadeAbility, Ultimate). Falls back to
     * the raw slot name when the agent/slot isn't mapped.
     */
    public static function abilityNameFor(string $agentName, string $slot): string
    {
        $slug = self::slug($agentName);

        return self::abilityMap()["{$slug}|{$slot}"] ?? $slot;
    }

    /**
     * In-memory (Cache, not DB) "{slug}|{slot}" => ability display name map.
     * Refreshed hourly by the agents:sync-abilities scheduled command; kept
     * for a day so a missed/slow refresh doesn't blank the map, and fetched
     * synchronously on a cold cache (first request after deploy).
     *
     * @return array<string, string>
     */
    private static function abilityMap(): array
    {
        $cached = Cache::get(self::ABILITY_CACHE_KEY);

        return $cached ?? self::fetchAbilityMap() ?? [];
    }

    /**
     * Fetches the ability map from valorant-api.com and stores it in cache.
     * Returns null (leaving any previously cached map untouched) on
     * failure. Public so the scheduled command can trigger it directly.
     *
     * @return array<string, string>|null
     */
    public static function fetchAbilityMap(): ?array
    {
        $response = Http::get('https://valorant-api.com/v1/agents', [
            'isPlayableCharacter' => 'true',
            'language' => 'en-US',
        ]);

        if (! $response->ok()) {
            return null;
        }

        $map = [];

        foreach ($response->json('data') ?? [] as $agent) {
            $slug = self::slug($agent['displayName'] ?? '');

            if ($slug === '') {
                continue;
            }

            foreach ($agent['abilities'] ?? [] as $ability) {
                $slot = self::ABILITY_SLOT_MAP[$ability['slot'] ?? ''] ?? null;
                $name = $ability['displayName'] ?? null;

                if ($slot && $name) {
                    $map["{$slug}|{$slot}"] = $name;
                }
            }
        }

        Cache::put(self::ABILITY_CACHE_KEY, $map, now()->addDay());

        return $map;
    }
}
