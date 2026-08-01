<?php

/**
 * GC-Stats — Prune old Data Explorer usage rows
 *
 * Data Explorer usage is already partitioned by year/month (see DataExplorerUsage),
 * so a new month "resets" on its own — claimRequestSlot()/usageSummary()
 * simply look up the current period's row, creating it at 0 if missing.
 * This command's actual job is table hygiene: dropping usage rows old
 * enough that nothing (quota math, the admin dashboard) still reads them.
 * Usage: php artisan app:reset-data-explorer-usage
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Models\DataExplorerUsage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reset-data-explorer-usage')]
#[Description('Prune Data Explorer usage rows older than 12 months')]
class ResetDataExplorerUsage extends Command
{
    private const RETENTION_MONTHS = 12;

    public function handle(): int
    {
        $cutoff = now()->subMonths(self::RETENTION_MONTHS);

        $deleted = DataExplorerUsage::where(function ($query) use ($cutoff) {
            $query->where('year', '<', $cutoff->year)
                ->orWhere(fn ($q) => $q->where('year', $cutoff->year)->where('month', '<', $cutoff->month));
        })->delete();

        $this->info("Pruned {$deleted} old Data Explorer usage row(s).");

        return self::SUCCESS;
    }
}
