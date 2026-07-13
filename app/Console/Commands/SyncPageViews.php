<?php

/**
 * GC-Stats — Sync buffered page views to the database
 *
 * Artisan command that reads the page view counters buffered in Redis
 * (per region/page) and persists them into the database, then clears
 * the Redis buffer.
 * Usage: php artisan app:sync-page-views
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

#[Signature('app:sync-page-views')]
#[Description('Insert page view into the DB by reading the cache')]
class SyncPageViews extends Command
{
    public function handle(): int
    {
        $currentHour = now()->startOfHour();

        // Atomically hand off the buffer to a work key so counters written
        // by concurrent requests between the read and the clear aren't lost.
        // RENAME itself is the atomicity boundary — no separate EXISTS check
        // beforehand, since that would leave a TOCTOU window if two syncs
        // (or a retry) race on the same source key.
        $workKey = 'page_views_buffer:sync:'.Str::uuid();

        try {
            Redis::rename('page_views_buffer', $workKey);
        } catch (\Throwable $e) {
            $this->info('PageView - No sync to be done.');

            return self::SUCCESS;
        }

        $views = Redis::hgetall($workKey);
        Redis::del($workKey);

        if (empty($views)) {
            $this->info('PageView - No sync to be done.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($views, $currentHour) {
            foreach ($views as $redisKey => $count) {
                $parts = explode('|', $redisKey, 2);
                $region = $parts[0] ?? 'OTHE';

                $subParts = explode(':', $parts[1] ?? '', 2);
                $country = $subParts[0] ?? 'UNK';
                $uri = $subParts[1] ?? '/';

                DB::table('page_views')->updateOrInsert(
                    [
                        'uri' => $uri,
                        'country' => $country,
                        'region' => $region,
                        'viewed_at' => $currentHour,
                    ],
                    [
                        'count' => DB::raw('count + '.(int) $count),
                    ]
                );
            }
        });

        $this->info('PageView - Sync done : '.$currentHour);

        return self::SUCCESS;
    }
}
