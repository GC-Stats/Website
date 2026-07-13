<?php

/**
 * GC-Stats — VCT regions configuration
 *
 * Defines display colors and groupings for VCT regions, used to style
 * tournament/team/match region badges across the site.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

return [
    // VCT America Color -> #FF6B35
    // VCT EMEA Color    -> #C8E000
    // VCT Pacific Color -> #00C8FF
    // VCT China Color   -> #FF1744

    'colors' => [
        'Americas' => '#FF6B35',
        'EMEA' => '#C8E000',
        'Pacific' => '#00C8FF',
        'China' => '#FF1744',
        'North America' => '#FF6B35',
        'Brazil' => '#FF6B35',
        'LATAM' => '#FF6B35',
        'SEA' => '#00C8FF',
    ],

    'riot_api' => [
        'Americas' => 'na',
        'EMEA' => 'eu',
        'Pacific' => 'ap',
        'China' => 'ap',
        'North America' => 'na',
        'Brazil' => 'br',
        'LATAM' => 'latam',
        'SEA' => 'ap',
    ],
];
