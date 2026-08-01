<?php

return [
    'title' => 'Widgets',
    'intro' => 'Browser-source-friendly overlays for broadcast production. Pick your options below and grab a link to drop straight into OBS.',

    'available' => [
        'head_to_head' => [
            'name' => 'Face to Face',
            'description' => 'Win-rate radar chart comparing two teams\' map pools, with an optional patch or custom map pool filter.',
        ],
        'heatmap' => [
            'name' => 'Positions Heatmap',
            'description' => 'Density heatmap of player positions on a map\'s minimap, filterable by tournament, date range, side, team, player, and event type.',
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

        'map' => 'Map',
        'map_placeholder' => 'Choose a map',
        'side' => 'Side',
        'side_all' => 'All sides',
        'side_atk' => 'Attack',
        'side_def' => 'Defense',
        'team' => 'Team (optional)',
        'player' => 'Player (optional)',
        'event_types' => 'Event types',
        'event_kill' => 'Kills',
        'event_plant' => 'Plants',
        'event_defuse' => 'Defuses',

        'agent' => 'Agent (optional)',
        'agent_all' => 'All agents',
        'color' => 'Heatmap color',
        'color_hint' => 'Also settable directly in the URL via ?color=RRGGBB.',

        'time_reference' => 'Time window starts from',
        'time_reference_round' => 'Round start',
        'time_reference_plant' => 'Plant',
        'time_start' => 'Time from (seconds)',
        'time_end' => 'Time to (seconds)',
        'time_hint' => 'Restricts positions to a window of the clock above, e.g. the opening 20 seconds of each round, or the first 10 seconds after each plant. The plant option excludes rounds that were never planted.',
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
