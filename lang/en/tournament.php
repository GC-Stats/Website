<?php

return [
    'index' => [
        'teams' => 'Teams',
        'prize_pool' => 'Prize Pool',
        'region' => 'Region',
        'location' => 'Location',
        'see_tournament' => 'Go to the tournament',
        'filter' => [
            'region' => [
                'title' => 'Region',
                'default' => 'Every regions',
            ],
            'category' => [
                'title' => 'Category',
                'default' => 'Every category',
            ],
            'period' => [
                'title' => 'Period',
                'default' => 'Every period',
            ],
        ],
        'sort' => [
            'title' => 'Sort_By',
            'date' => 'Date',
            'name' => 'Name',
        ],
    ],

    'title' => [
        'nav' => 'Tournaments',
        'index' => ':tournament',
        'matches' => ':tournament - Matches',
        'stats' => ':tournament - Stats',
        'maps' => ':tournament - Maps',
    ],

    'teams_participating' => 'Participating Teams',
    'show_roster' => 'Show roster',
    'hide_roster' => 'Hide roster',
    'show_all_rosters' => 'Show all rosters',
    'hide_all_rosters' => 'Hide all rosters',
    'last_matches' => 'Latest Matches',
    'inactive_access' => 'This tournament is not publicly visible yet. You can see it because your role grants access to inactive tournaments.',
    'no_match' => 'No matches',
    'seemore' => 'See more',
    'go_back' => 'Go back',
    'swiss_stage' => [
        'team' => 'Teams',
        'matches' => 'Matches (W-L)',
        'maps' => 'Maps (W-L)',
        'rounds' => 'Round Diff (+/-)',
        'qualification_single' => 'The top :rank is qualified for :destination.',
        'qualification_range' => 'The top :from-:to are qualified for :destination.',
    ],
    'nav' => [
        'aria_label' => 'Tournament navigation',
        'overview' => 'Overview',
        'matches' => 'Matches',
        'stats' => 'Stats',
        'maps' => 'Maps',
    ],

    'bracket' => [
        'label' => 'Tournament bracket (pannable and zoomable)',
        'qualified_tooltip' => ':team is qualified for :destination',
        'qualifiers_column' => 'Qualified',
    ],

    'leaderboard' => [
        'title' => 'Results',
        'team' => 'Team',
        'points' => 'Points',
        'cash_prize' => 'Cash prize',
        'destination' => 'Qualified for',
    ],

    'stats' => [
        'title' => ':tournament – Statistics',
        'period' => 'Period: ',
        'phase' => 'Phase: ',
        'all_phases' => 'All phases',
        'no_data' => 'No data available',
    ],

    'filters' => [
        'phase' => 'Phase: ',
        'all_phases' => 'All phases',
        'search_phase' => 'Search a phase...',
        'team' => 'Team',
        'all_teams' => 'All teams',
        'search_team' => 'Search a team...',
        'round' => 'Round: ',
        'all_rounds' => 'All rounds',
    ],

    'maps' => [
        'title' => ':tournament – Maps',
        'times_played' => 'Times played',
        'atk_wr' => 'ATK Win%',
        'def_wr' => 'DEF Win%',
        'pick_rate' => 'Agent pick rate',
        'comps' => 'Comps played',
        'played_by' => 'Played by',
        'vs' => 'vs',
        'no_data' => 'No maps played yet',
    ],
];
