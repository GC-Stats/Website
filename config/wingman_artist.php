<?php

/**
 * GC-Stats — Wingman artwork credit
 *
 * Attribution shown in the "Wingman credits" modal (header logo + the
 * "More plants" stats easter egg, see public/partials/wingman-credit.blade.php
 * and public/partials/wingman-modal.blade.php). Fill in once the artist is
 * confirmed; any social left null/empty is simply not rendered.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

return [
    'name' => 'Riot Games',

    // Path under /storage, or null to fall back to the wingman artwork itself.
    'avatar' => null,

    'bio' => 'This image is made by Riot Games / Valorant has a in-game spray',

    // key => full profile URL. Supported keys get a matching brand icon
    // (twitter/x, instagram, twitch, discord, github, tiktok, youtube,
    // bluesky); anything else falls back to a generic link icon.
    'socials' => [
        // 'twitter' => 'https://twitter.com/handle',
        // 'instagram' => 'https://instagram.com/handle',
        // 'website' => 'https://example.com',
    ],
];
