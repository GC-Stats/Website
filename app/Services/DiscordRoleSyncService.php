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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordRoleSyncService
{
    /**
     * Fetch $user's current Discord guild roles and re-apply every mapped
     * app role (per team scope). Returns false if the user has no linked
     * Discord account or the API call failed. Used for one-off real-time
     * syncs (e.g. right after linking a Discord account) — for re-syncing
     * every linked user in bulk, see syncAll(), which avoids one API call
     * per user.
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

        $this->applyRoles($user, $response->json('roles', []), $this->mappingsByTeam());
        $user->forceFill(['discord_synced_at' => now()])->save();

        return true;
    }

    /**
     * Re-sync every Discord-linked user in one guild-member list walk
     * instead of one API call and one mapping query per user — used by the
     * discord:sync-roles scheduled command.
     *
     * @return array{synced: int, total: int}
     */
    public function syncAll(): array
    {
        $guildRolesByDiscordId = $this->fetchGuildMemberRoles();

        $users = User::whereHas('socialAccounts', fn ($q) => $q->where('provider', 'discord'))
            ->with(['socialAccounts' => fn ($q) => $q->where('provider', 'discord')])
            ->get();

        if ($guildRolesByDiscordId === null) {
            return ['synced' => 0, 'total' => $users->count()];
        }

        $mappingsByTeam = $this->mappingsByTeam();
        $synced = 0;

        foreach ($users as $user) {
            $discordAccount = $user->socialAccounts->first();
            $discordRoleIds = $guildRolesByDiscordId[$discordAccount?->provider_id] ?? null;

            // Not present in the guild-members walk at all means the user
            // left the guild — existing mapped roles are left as-is, same
            // as the 404 case in sync().
            if ($discordRoleIds === null) {
                continue;
            }

            $this->applyRoles($user, $discordRoleIds, $mappingsByTeam);
            $user->forceFill(['discord_synced_at' => now()])->save();
            $synced++;
        }

        return ['synced' => $synced, 'total' => $users->count()];
    }

    /**
     * Walks every page of the guild's member list (up to 1000 per page, the
     * Discord API max) instead of issuing one members/{user} call per user,
     * and returns discord user id => their current guild role ids. Returns
     * null if any page fails to load.
     *
     * @return ?array<string, list<string>>
     */
    private function fetchGuildMemberRoles(): ?array
    {
        $rolesByDiscordId = [];
        $after = '0';

        do {
            $response = Http::withToken(config('services.discord.bot_token'), 'Bot')
                ->timeout(10)
                ->get(sprintf('https://discord.com/api/v10/guilds/%s/members', config('services.discord.guild_id')), [
                    'limit' => 1000,
                    'after' => $after,
                ]);

            if ($response->failed()) {
                Log::warning('Discord guild member list fetch failed', ['status' => $response->status()]);

                return null;
            }

            $page = $response->json();

            foreach ($page as $member) {
                $rolesByDiscordId[$member['user']['id']] = $member['roles'];
            }

            $after = $page === [] ? null : end($page)['user']['id'];
        } while (count($page) === 1000);

        return $rolesByDiscordId;
    }

    /**
     * @return Collection<int|string, Collection<int, DiscordRoleMapping>>
     */
    private function mappingsByTeam(): Collection
    {
        return DiscordRoleMapping::query()
            ->select('team_id', 'app_role', 'discord_role_id')
            ->get()
            ->groupBy(fn (DiscordRoleMapping $mapping) => $mapping->team_id ?? PermissionTeam::GLOBAL_ID);
    }

    /**
     * @param  list<string>  $discordRoleIds
     * @param  Collection<int|string, Collection<int, DiscordRoleMapping>>  $allMappingsByTeam
     */
    private function applyRoles(User $user, array $discordRoleIds, Collection $allMappingsByTeam): void
    {
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

        PermissionTeam::global();
    }
}
