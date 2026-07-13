<?php

/**
 * GC-Stats — Cache TTL helper
 *
 * Provides cache time-to-live durations based on the status (live, upcoming,
 * finished, etc.) of tournaments and matches.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class CacheTtl
{
    public static function forTournament(string $status): CarbonInterface
    {
        return self::forStatus($status);
    }

    public static function forMatch(string $status): CarbonInterface
    {
        return self::forStatus($status);
    }

    private static function forStatus(string $status): CarbonInterface
    {
        return match ($status) {
            'live' => Carbon::now()->addMinutes(2),
            'upcoming' => Carbon::now()->addHours(24),
            'finished' => Carbon::now()->addDays(7),
            default => Carbon::now()->addHours(1),
        };
    }
}
