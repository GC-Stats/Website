<?php

/**
 * GC-Stats — Discord role sync command
 *
 * Re-applies mapped Discord guild roles to every user with a linked Discord
 * account, so role changes made in Discord (promotions, staff additions...)
 * eventually reflect on the site even without the user logging back in.
 * Usage: php artisan discord:sync-roles
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Services\DiscordRoleSyncService;
use Illuminate\Console\Command;

class SyncDiscordRoles extends Command
{
    protected $signature = 'discord:sync-roles';

    protected $description = "Re-sync every linked user's Discord guild roles to their application roles";

    public function handle(DiscordRoleSyncService $service): int
    {
        $result = $service->syncAll();

        $this->info("Synced {$result['synced']}/{$result['total']} users.");

        return self::SUCCESS;
    }
}
