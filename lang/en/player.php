<?php

return [
    'title' => [
        'index' => ':player',
        'history' => ':player - Team History',
        'matches' => ':player - Matches',
        'stats' => ':player - Stats',
    ],

    'current_team' => 'Current Team',
    'old_team' => 'Former Teams',
    'no_team' => 'No Team',
    'news' => 'News',
    'seemore' => 'See more',

    // Other pages text
    'matches_history' => ':player – Matches History',
    'teams_history' => ':player – Teams History',

    'stats' => [
        'title' => ':player – Statistics',
        'period' => 'Period: ',
        'no_data' => 'No data available',
        'date_filter' => 'Filter by date range',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'filter_submit' => 'Apply date filter',
    ],

    'empty' => [
        'matches_history' => 'This player has no matches',
        'players_history' => 'This player has no past teams',
    ],

    'errors' => [
        'multiple_active_teams' => 'A player can only have one active team at a time.',
    ],

    'nav' => [
        'aria_label' => 'Player navigation',
        'overview' => 'Overview',
        'matches' => 'Matches',
        'stats' => 'Stats',
        'teams_history' => 'Teams history',
    ],

    'edit' => [
        'title' => 'Edit player',
        'logo' => [
            'title' => 'Photo',
            'submit' => 'Upload',
            'history_title' => 'Photo history',
            'history_from' => 'From',
            'history_until' => 'Until',
            'history_add' => 'Add to history',
            'history_remove_confirm' => 'Permanently remove this photo history entry?',
            'history_empty' => 'No past photos.',
        ],
        'profile' => [
            'title' => 'Profile',
            'submit' => 'Save changes',
        ],
        'fields' => [
            'handle' => 'Handle',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'country_code' => 'Country code',
            'country_code_search' => 'Search for a country…',
            'country_code_none' => 'No country / international',
            'bio' => 'Description',
            'vlr_id' => 'VLR.gg ID',
            'vlr_id_info' => 'Not displayed or shared publicly — used internally to simplify our work.',
            'liquipedia_link' => 'Liquipedia link',
            'is_active' => 'Active player',
            'socials' => 'Social accounts',
        ],
        'team_history' => [
            'title' => 'Current team(s)',
            'history_title' => 'Team history',
            'add' => 'Add a team',
            'remove_confirm' => 'Permanently remove this team-history entry for :team?',
            'current_empty' => 'Not currently on a team.',
            'history_empty' => 'No past teams.',
        ],
    ],
];
