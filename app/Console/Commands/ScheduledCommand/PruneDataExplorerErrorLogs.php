<?php

/**
 * GC-Stats — Prune old Data Explorer error logs
 *
 * data_explorer_error_logs exists to let a user's "Error ID" reference
 * something real for support/debugging — it's not meant to be a permanent
 * audit trail, so rows older than the retention window are dropped.
 * Usage: php artisan app:prune-data-explorer-error-logs
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Models\DataExplorerErrorLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:prune-data-explorer-error-logs')]
#[Description('Prune Data Explorer error logs older than 30 days')]
class PruneDataExplorerErrorLogs extends Command
{
    private const RETENTION_DAYS = 30;

    public function handle(): int
    {
        $deleted = DataExplorerErrorLog::where('created_at', '<', now()->subDays(self::RETENTION_DAYS))->delete();

        $this->info("Pruned {$deleted} old Data Explorer error log(s).");

        return self::SUCCESS;
    }
}
