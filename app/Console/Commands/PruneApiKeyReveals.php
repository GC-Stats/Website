<?php

/**
 * GC-Stats — Prune expired API key reveal links
 *
 * Deletes api_key_reveals rows whose expiry has passed, whether or not
 * they were ever viewed, so the table (and its encrypted key blobs)
 * doesn't grow unbounded.
 * Usage: php artisan app:prune-api-key-reveals
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Models\ApiKeyReveal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:prune-api-key-reveals')]
#[Description('Delete expired API key reveal links')]
class PruneApiKeyReveals extends Command
{
    public function handle(): int
    {
        $deleted = ApiKeyReveal::where('expires_at', '<', now())->delete();

        $this->info("Pruned {$deleted} expired API key reveal(s).");

        return self::SUCCESS;
    }
}
