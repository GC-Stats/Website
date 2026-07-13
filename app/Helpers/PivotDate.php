<?php

/**
 * GC-Stats — Pivot date formatter
 *
 * Formats nullable date values, treating the conventional "1900-01-01"
 * placeholder date as an "UNKNOWN" value instead of a real date.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Helpers;

use Carbon\Carbon;

class PivotDate
{
    public static function format(?string $value, string $format): ?string
    {
        if (! $value) {
            return null;
        }

        $date = Carbon::parse($value);

        return $date->isSameDay(Carbon::parse('1900-01-01')) ? 'UNKNOWN' : $date->format($format);
    }

    public static function isUnknown(?string $value): bool
    {
        if (! $value) {
            return true;
        }

        return Carbon::parse($value)->isSameDay(Carbon::parse('1900-01-01'));
    }
}
