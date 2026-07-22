<?php

return [
    'index' => [
        'teams' => 'Équipes',
        'prize_pool' => 'Cagnotte',
        'region' => 'Région',
        'location' => 'Lieu',
        'see_tournament' => 'Aller au tournoi',
        'filter' => [
            'region' => [
                'title' => 'Région',
                'default' => 'Toutes les régions',
            ],
            'category' => [
                'title' => 'Catégorie',
                'default' => 'Toutes les catégories',
            ],
            'period' => [
                'title' => 'Période',
                'default' => 'Toutes les périodes',
            ],
        ],
        'sort' => [
            'title' => 'Trier_Par',
            'date' => 'Date',
            'name' => 'Nom',
        ],
    ],

    'title' => [
        'nav' => 'Tournois',
        'index' => ':tournament',
        'matches' => ':tournament - Matches',
        'stats' => ':tournament - Statistiques',
        'maps' => ':tournament - Maps',
    ],

    'teams_participating' => 'Équipes participantes',
    'show_roster' => 'Voir le roster',
    'hide_roster' => 'Masquer le roster',
    'show_all_rosters' => 'Afficher tous les rosters',
    'hide_all_rosters' => 'Masquer tous les rosters',
    'last_matches' => 'Derniers matchs',
    'inactive_access' => "Ce tournoi n'est pas encore visible publiquement. Vous pouvez le voir car votre rôle donne accès aux tournois inactifs.",
    'no_match' => 'Aucun match',
    'seemore' => 'Voir plus',
    'go_back' => 'Revenir en arrière',
    'swiss_stage' => [
        'team' => 'Équipes',
        'matches' => 'Matchs (W-L)',
        'maps' => 'Maps (W-L)',
        'rounds' => 'Diff. Rounds (+/-)',
        'qualification_single' => 'Le top :rank est qualifié pour :destination.',
        'qualification_range' => 'Les top :from-:to sont qualifiés pour :destination.',
    ],
    'nav' => [
        'aria_label' => 'Navigation du tournoi',
        'overview' => "Vue d'ensemble",
        'matches' => 'Matchs',
        'stats' => 'Statistiques',
        'maps' => 'Maps',
    ],

    'bracket' => [
        'label' => 'Bracket du tournoi (déplaçable et zoomable)',
        'qualified_tooltip' => ':team est qualifié pour :destination',
        'qualifiers_column' => 'Qualifiés',
    ],

    'leaderboard' => [
        'title' => 'Résultats',
        'team' => 'Équipe',
        'points' => 'Points',
        'cash_prize' => 'Cashprize',
        'destination' => 'Qualifié pour',
    ],

    'stats' => [
        'title' => ':tournament – Statistiques',
        'period' => 'Période : ',
        'phase' => 'Phase : ',
        'all_phases' => 'Toutes les phases',
        'no_data' => 'Aucune donnée disponible',
    ],

    'filters' => [
        'phase' => 'Phase : ',
        'all_phases' => 'Toutes les phases',
        'search_phase' => 'Rechercher une phase...',
        'team' => 'Équipe',
        'all_teams' => 'Toutes les équipes',
        'search_team' => 'Rechercher une équipe...',
        'round' => 'Round : ',
        'all_rounds' => 'Tous les rounds',
        'status' => 'Statut : ',
        'all_statuses' => 'Tous les statuts',
    ],

    'maps' => [
        'title' => ':tournament – Maps',
        'times_played' => 'Fois jouée',
        'atk_wr' => 'Winrate ATK',
        'def_wr' => 'Winrate DEF',
        'pick_rate' => 'Pick rate des agents',
        'comps' => 'Comps jouées',
        'played_by' => 'Jouée par',
        'vs' => 'vs',
        'no_data' => 'Aucune map jouée pour le moment',
    ],
];
