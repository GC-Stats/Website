<?php

/**
 * GC-Stats — Social links parser
 *
 * Normalizes the "socials" array returned by the HenrikDev API (platform +
 * profile URL) into a flat [platform => username] map. Shared between the
 * player and team importers.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class SocialsParser
{
    /**
     * @param  array<int, array{platform?: string, url?: string}>  $socials
     * @return array<string, string>
     */
    public static function parse(array $socials): array
    {
        $result = [];

        foreach ($socials as $social) {
            $platform = strtolower($social['platform'] ?? '');
            $username = basename($social['url'] ?? '');

            if ($platform && $username) {
                $result[$platform] = $username;
            }
        }

        return $result;
    }
}
