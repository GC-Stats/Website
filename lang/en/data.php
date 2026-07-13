<?php

return [
    'title' => 'Data Structure',
    'styled_title' => 'Data <span class="text-gc-yellow">Structure</span>',
    'subtitle' => 'Structure and list of collected data',

    'opendata' => [
        'title' => 'Open Data Portal',
        'body' => 'Want to explore or download our data directly? Check out our Open Data portal.',
        'btn' => 'Visit Open Data Portal',
    ],

    'titles' => [
        'player_team' => 'Players & Teams Structure',
        'tournament' => 'Tournaments Structure',
        'matches' => 'Matches Structure',
        'news' => 'News Structure',
        'others' => 'Other Data Structure',
    ],

    'descriptions' => [
        'player_team' => "Each team/player must have a name and an identifier; other information is added by the team/player or by administrators via tournament data.<br>Players/teams have the right to modify/delete 'Additional Information'.<br><br>The val_id and discord_id are not displayed or shared. They are provided by the player via Discord.",
        'tournament' => 'Each tournament must have an identifier, a name, dates, a region, a category, and a status. They are collected by our team or upon request during a tournament addition request.',
        'matches' => "We collect a lot of match information to provide detailed statistics. We retrieve:<br> - Basic match information (Mandatory)<br> - Match vetos<br> - Played maps, with results and stats<br> - Global match stats<br> - Each round's result (Who wins, and how)<br> - Each player's stats per round (KDA/Economy/Weapon/Armor/etc)<br><br>These stats are not always collected, but we strive to obtain them to display the most complete statistics possible.",
        'news' => 'Each news item must have an identifier, a title, content, and an author.<br><br>The status is hidden but allows for display (Draft = In progress, Published = Visible on site, Archived = Hidden on site but accessible via API).',
        'others' => 'We store the number of visits per page per hour; we do not store who visited, from which region, or the exact time.<br><br>This data is stored and private. It is never shared; we use it to visualize site usage and adapt our infrastructure as needed.',
    ],

    'players' => [
        'titles' => [
            'main' => 'Main Information',
            'additional' => 'Additional Information',
            'confidential' => 'Confidential Information',
        ],
        'id' => 'Unique identifier assigned to a player (Visible in URL)',
        'handle' => 'Username',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'country_code' => 'Country Code (FR = France, US = United States, etc.)',
        'bio' => 'Biography',
        'socials' => 'Social Media (Twitter/Instagram/Twitch/Youtube/TikTok)',
        'discord_id' => 'Discord ID (Private information, used to validate a player\'s identity when modifying their profile)',
        'val_id' => 'Riot ID (Private information, identified and assigned by our team from Riot match data, used to link match statistics with a player)',
        'vlr_id' => 'Player\'s VLR ID',
    ],

    'player_team' => [
        'cascade' => 'Deleting a player or a team automatically deletes linked elements.',
        'player_id' => 'Unique identifier for a player',
        'team_id' => 'Unique identifier for a team',
        'role' => 'Player/Coach/Manager/etc.',
        'joined_at' => 'Date joined the team',
        'left_at' => 'Date left the team',
    ],

    'teams' => [
        'titles' => [
            'main' => 'Main Information',
            'additional' => 'Additional Information',
        ],
        'id' => 'Unique identifier assigned to a team (Visible in URL)',
        'name' => 'Team Name',
        'short_name' => 'Short Name (Fnatic = FNC)',
        'country_code' => 'Country Code (FR = France, US = United States, etc.)',
        'bio' => 'Biography',
        'website' => 'Website',
        'socials' => 'Social Media (Twitter/Instagram/Twitch/Youtube/TikTok)',
        'vlr_id' => 'Team\'s VLR ID',
    ],

    'tournaments' => [
        'titles' => [
            'main' => 'Main Information',
            'additional' => 'Additional Information',
        ],
        'id' => 'Unique tournament identifier',
        'name' => 'Competition Name',
        'region' => 'World Region (EMEA, NA, etc.)',
        'category' => 'Tournament Category (e.g., Challengers, Game Changers)',
        'prize_pool' => 'Total Prize Pool',
        'location' => 'Physical Location',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'status' => 'Current Status (Upcoming, Live, Finished)',
        'description' => 'Event presentation',
    ],

    'tournament_phases' => [
        'titles' => [
            'structure' => 'Technical Structure',
        ],
        'tournament_id' => 'Reference to the concerned tournament',
        'name' => 'Phase Name (e.g., Playoffs, Group A)',
        'format' => 'Game Mode (e.g., Single Elimination, Round Robin)',
        'parent_id' => 'Link to a parent phase (for subgroups)',
        'order' => 'Phase display order',
    ],

    'tournament_teams' => [
        'link' => 'Linking Table: Connects teams to the tournaments they participate in.',
        'team_list' => 'List of teams registered for the event',
    ],

    'matches' => [
        'titles' => [
            'structure' => 'Technical Structure',
        ],
        'id' => 'Unique match identifier',
        'tournament_id' => 'Tournament reference',
        'phase_id' => 'Phase reference (e.g., Playoffs)',
        'round_number' => 'Round number (e.g., Round 1)',
        'round_name' => 'Round name (e.g., Grand Final)',
        'match_order' => 'Match order',
        'team_a_id' => 'Unique ID Team A',
        'team_b_id' => 'Unique ID Team B',
        'scheduled_at' => 'Scheduled start time',
        'status' => 'Status (upcoming, live, finished)',
        'team_a_score' => 'Final Score Team A',
        'team_b_score' => 'Final Score Team B',
        'best_of' => 'Maximum maps (BO1, BO3, BO5)',
        'patch' => 'Game version (e.g., 8.04)',
    ],

    'match_vetos' => [
        'id' => 'Unique veto identifier',
        'match_id' => 'Concerned match',
        'team_id' => 'Team performing the action',
        'map_name' => 'Map name (e.g., Ascent)',
        'type' => 'Action (Pick, Ban, or Decider)',
        'order' => 'Order in the veto sequence',
    ],

    'game_maps' => [
        'id' => 'Unique played map identifier',
        'api_match_id' => 'Unique ID of the map, given by Riot API',
        'match_id' => 'Concerned match',
        'map_name' => 'Map name',
        'team_a_score' => 'Team A score on this map',
        'team_b_score' => 'Team B score on this map',
        'order' => 'Map position in the match',
        'is_completed' => 'Is the map completed?',
    ],

    'game_player_stats' => [
        'id' => 'Unique stat identifier',
        'match_id' => 'Concerned match',
        'game_map_id' => 'Concerned map',
        'player_id' => 'Concerned player',
        'team_id' => 'Team at the time of the match',
        'agent_name' => 'Played Agent (e.g., Jett)',
        'kills' => 'Total Kills',
        'deaths' => 'Total Deaths',
        'assists' => 'Total Assists',
        'acs' => 'Average Combat Score',
        'adr' => 'Average Damage per Round',
        'kast_percentage' => '% of rounds with Kill/Assist/Survival/Trade',
        'first_kills' => 'First Kills (FK)',
        'first_deaths' => 'First Deaths (FD)',
        'headshot_percentage' => 'Headshot %',
    ],

    'game_map_rounds' => [
        'id' => 'Unique round identifier',
        'game_map_id' => 'Concerned map',
        'round_number' => 'Round number (1-24+)',
        'winning_team' => 'Winning team',
        'win_type' => 'Win type (Defuse, Detonation, Wipe)',
    ],

    'game_map_round_player_stats' => [
        'id' => 'Unique round stat identifier',
        'game_map_round_id' => 'Concerned round',
        'player_id' => 'Concerned player',
        'kills' => 'Kills during this round',
        'assists' => 'Assists during this round',
        'score' => 'Score obtained this round',
        'economy_spent' => 'Credits spent',
        'economy_remaining' => 'Credits remaining',
        'weapon_id' => 'Main weapon used',
        'armor' => 'Armor type (Light/Heavy)',
    ],

    'news' => [
        'titles' => [
            'main' => 'Main Information',
            'additional' => 'Additional Information',
        ],
        'id' => 'Unique news identifier (Visible in URL)',
        'author' => 'Author name',
        'title' => 'News title',
        'content' => 'News content',
        'status' => 'News status (draft/published/archived)',
        'is_featured' => 'Whether news is displayed on the homepage',
        'published_at' => 'Publication date',
        'relation' => 'Link between news and a tournament/team/player, allows display on profile.',
    ],

    'others' => [
        'titles' => [
            'main' => 'Main Information',
        ],
        'uri' => 'Page URL',
        'viewed_at' => 'Time of viewing',
        'count' => 'Number of views',
    ],
];
