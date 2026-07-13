<?php

/**
 * GC-Stats — Bunny CDN cache purge service
 *
 * Wraps the Bunny.net purge API to invalidate cached pages on the CDN
 * whenever underlying data (matches, players, teams, tournaments) changes.
 * No-op outside production environments or without an API key configured.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Jobs\PurgeBunnyCache;
use Illuminate\Support\Facades\Log;

class BunnyCache
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.bunny.api_key');
    }

    public function purgeUrls(array $urls): void
    {
        if (empty($urls) || app()->environment('local', 'testing')) {
            return;
        }

        if (empty($this->apiKey)) {
            Log::warning('BunnyCache::purgeUrls skipped: BUNNY_API_KEY is not configured.', ['urls' => $urls]);

            return;
        }

        PurgeBunnyCache::dispatch($urls);
    }
}
