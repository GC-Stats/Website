<?php

/**
 * GC-Stats — Sync agent ability names
 *
 * Artisan command that pulls every playable agent's ability names from
 * valorant-api.com and caches them in memory (no DB table — the mapping is
 * small and disposable) as a flat "{slug}|{slot}" => name array, keyed by
 * the same lowercase slash-stripped slug used across the app
 * (App\Helpers\AgentRoles) and the kill-feed slot (Ability1/Ability2/
 * GrenadeAbility/Ultimate). Scheduled hourly; App\Helpers\AgentRoles reads
 * this cache and re-triggers the fetch itself if it's ever cold.
 * Usage: php artisan agents:sync-abilities
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Helpers\AgentRoles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('agents:sync-abilities')]
#[Description('Fetch agent ability names from valorant-api.com into cache')]
class SyncAgentAbilities extends Command
{
    public function handle(): int
    {
        $map = AgentRoles::fetchAbilityMap();

        if ($map === null) {
            $this->error('valorant-api.com request failed');

            return self::FAILURE;
        }

        $this->info('Synced abilities for '.count($map).' slots');

        return self::SUCCESS;
    }
}
