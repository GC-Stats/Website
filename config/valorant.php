<?php

/**
 * GC-Stats — Valorant reference data
 *
 * Static pools used by admin forms that need to pick from Riot's fixed
 * agent/weapon/armor lists (e.g. manual map stat entry) rather than free
 * text.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

return [
    'agents' => [
        'Astra', 'Breach', 'Brimstone', 'Chamber', 'Clove', 'Cypher', 'Deadlock', 'Fade',
        'Gekko', 'Harbor', 'Iso', 'Jett', 'KAY/O', 'Killjoy', 'Neon',
        'Omen', 'Phoenix', 'Raze', 'Reyna', 'Sage', 'Skye', 'Sova', 'Tejo',
        'Viper', 'Vyse', 'Waylay', 'Yoru',
    ],

    'weapons' => [
        'Classic', 'Shorty', 'Frenzy', 'Ghost', 'Bandit', 'Sheriff',
        'Stinger', 'Spectre', 'Bucky', 'Judge',
        'Bulldog', 'Guardian', 'Phantom', 'Vandal',
        'Marshal', 'Outlaw', 'Operator',
        'Ares', 'Odin',
        'Melee',
    ],

    'armor_types' => ['None', 'Light Armor', 'Regen Shield', 'Heavy Armor'],

    // Matches the raw values Riot's API returns for a round's `roundResult`
    // (also used verbatim as the round-history win icon filenames, see
    // resources/views/partials/round-history.blade.php).
    'win_types' => ['Eliminated', 'Bomb defused', 'Bomb detonated', 'Round timer expired'],
];
