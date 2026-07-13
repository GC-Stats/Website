<?php

/**
 * GC-Stats — Person name splitting helper
 *
 * Shared logic to split a "real name" string coming from the HenrikDev API
 * into first/last name parts. Used by both the player and team importers so
 * the same person always gets the same first_name/last_name regardless of
 * which import path (roster vs standalone player) resolved them.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class PersonName
{
    /**
     * @return array{first: ?string, last: ?string}
     */
    public static function split(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['first' => null, 'last' => null];
        }

        $parts = explode(' ', $name);
        $count = count($parts);

        if ($count === 1) {
            return ['first' => $parts[0], 'last' => null];
        }

        if ($count === 2) {
            return ['first' => $parts[0], 'last' => $parts[1]];
        }

        $last = array_pop($parts);
        $first = implode(' ', $parts);

        return ['first' => $first, 'last' => $last];
    }
}
