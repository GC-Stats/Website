<?php

return [
    'title' => 'Widgets',
    'intro' => 'Browser-source-friendly overlays for broadcast production. Pick your options below and grab a link to drop straight into OBS.',

    'available' => [
        'head_to_head' => [
            'name' => 'Face to Face',
            'description' => 'Win-rate radar chart comparing two teams\' map pools, with an optional patch or custom map pool filter.',
        ],
    ],

    'configure' => 'Configure',
    'no_preview' => 'No preview available yet',

    'builder' => [
        'team_a' => 'Team A',
        'team_b' => 'Team B',
        'tournament' => 'Tournament (optional)',
        'tournament_hint' => 'Restricts the comparison to games played in this tournament.',
        'start_date' => 'Start date (optional)',
        'end_date' => 'End date (optional)',
        'patch' => 'Patch (optional)',
        'patch_placeholder' => 'e.g. 9.1',
        'patch_hint' => 'Limits the map pool to maps played under this patch.',
        'mappool' => 'Map pool override (optional)',
        'mappool_placeholder' => 'e.g. Ascent, Bind, Haven',
        'mappool_hint' => 'Comma-separated map names. Takes priority over the patch above.',
        'submit' => 'Generate link',
    ],

    'result' => [
        'title' => 'Your widget link',
        'copy' => 'Copy link',
        'copied' => 'Copied!',
        'open' => 'Open',
        'preview' => 'Preview',
        'embed_hint' => 'In OBS, add a Browser Source with this URL. The background is transparent.',
    ],
];
