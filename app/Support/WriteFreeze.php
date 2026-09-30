<?php

/**
 * GC-Stats — Public write freeze
 *
 * Read-only switch used while the database is migrated to V2: every public
 * action creating or editing migrated data (change requests, forum, reactions,
 * user reports) is refused, the rest of the site stays up. Admin is not gated.
 * Stored in the shared cache so it applies to every app instance at once.
 * Toggled with `php artisan app:write-freeze on|off|status`.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class WriteFreeze
{
    private const CACHE_KEY = 'write-freeze:since';

    public static function active(): bool
    {
        return Cache::has(self::CACHE_KEY);
    }

    public static function since(): ?Carbon
    {
        $timestamp = Cache::get(self::CACHE_KEY);

        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    public static function enable(): void
    {
        Cache::forever(self::CACHE_KEY, now()->getTimestamp());
    }

    public static function disable(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function abortIfActive(): void
    {
        abort_if(self::active(), 503, __('layout.write_freeze.blocked'));
    }
}
