<?php

/**
 * GC-Stats — Discord role sync service
 *
 * Reads a user's roles on the GC-Stats Discord guild (via the bot token,
 * since a periodic sync can't rely on the user being logged in) and maps
 * them to application roles through `discord_role_mappings`, applying them
 * with spatie/laravel-permission. Only mapped roles are touched — a user's
 * non-Discord-sourced roles (assigned manually) are left untouched.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\DiscordRoleMapping;
use App\Models\User;
use App\Support\PermissionTeam;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordRoleSyncService
{
    /**
     * Fetch $user's current Discord guild roles and re-apply every mapped
     * app role (per team scope). Returns false if the user has no linked
     * Discord account or the API call failed.
     */
    public function sync(User $user): bool
    {
        $discordAccount = $user->socialAccounts()->where('provider', 'discord')->first();

        if (! $discordAccount) {
            return false;
        }

        $response = Http::withToken(config('services.discord.bot_token'), 'Bot')
            ->timeout(5)
            ->get(sprintf(
                'https://discord.com/api/v10/guilds/%s/members/%s',
                config('services.discord.guild_id'),
                $discordAccount->provider_id,
            ));

        if ($response->status() === 404) {
            // User left the guild — nothing to sync, existing mapped roles are left as-is.
            return true;
        }

        if ($response->failed()) {
            Log::warning('Discord role sync failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
            ]);

            return false;
        }

        $discordRoleIds = $response->json('roles', []);

        // team_id is nullable (a mapping with no team is a site-wide role).
        // Collection::groupBy() would otherwise coerce that null key to ""
        // (PHP array-key coercion), which then fails PermissionTeam::use()'s
        // ?int signature — normalize to the global sentinel before grouping.
        $allMappingsByTeam = DiscordRoleMapping::query()
            ->select('team_id', 'app_role', 'discord_role_id')
            ->get()
            ->groupBy(fn (DiscordRoleMapping $mapping) => $mapping->team_id ?? PermissionTeam::GLOBAL_ID);

        foreach ($allMappingsByTeam as $teamId => $teamMappings) {
            PermissionTeam::use((int) $teamId);

            $desiredRoles = $teamMappings
                ->whereIn('discord_role_id', $discordRoleIds)
                ->pluck('app_role')
                ->unique()
                ->values();

            $discordManagedRoles = $teamMappings->pluck('app_role')->unique();

            $keptRoles = $user->roles()->pluck('name')->diff($discordManagedRoles);

            $user->syncRoles($keptRoles->merge($desiredRoles)->values()->all());
        }

        $user->forceFill(['discord_synced_at' => now()])->save();

        return true;
    }
}
