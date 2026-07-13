<?php

/**
 * GC-Stats — Valorant agent roles
 *
 * Maps each agent's icon slug (lowercase, slash-stripped — matching the
 * `/storage/agents/{slug}.webp` filenames) to its role, used to color-code
 * agent icons on the maps pages.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

return [
    'duelist' => ['iso', 'jett', 'neon', 'phoenix', 'raze', 'reyna', 'waylay', 'yoru'],
    'initiator' => ['breach', 'fade', 'gekko', 'kayo', 'skye', 'sova', 'tejo'],
    'controller' => ['astra', 'brimstone', 'clove', 'harbor', 'miks', 'omen', 'viper'],
    'sentinel' => ['chamber', 'cypher', 'deadlock', 'killjoy', 'sage', 'veto', 'vyse'],
];
