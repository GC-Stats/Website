<?php

/**
 * GC-Stats — Permission catalog base
 *
 * Shared shape for the site-wide (AdminPermissions) and per-team
 * (TeamPermissions) permission catalogs: each just supplies its own
 * grouped() list and gets all() for free.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

abstract class PermissionCatalog
{
    /**
     * @return array<string, list<string>> permission names grouped by section, for display
     */
    abstract public static function grouped(): array;

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(static::grouped()));
    }
}
