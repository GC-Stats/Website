<?php

use App\Support\Pronouns;

return [
    'title' => [
        'index' => ':player',
        'history' => ':player - Historique des équipes',
        'matches' => ':player - Matches',
        'stats' => ':player - Statistiques',
    ],

    'current_team' => 'Équipe Actuelle',
    'old_team' => 'Anciennes Équipes',
    'no_team' => 'Aucune Équipe',
    'news' => 'Actualités',
    'seemore' => 'Voir plus',

    // Other pages text
    'matches_history' => ':player - Historiques des matches',
    'teams_history' => ':player - Historique des équipes',

    'stats' => [
        'title' => ':player - Statistiques',
        'period' => 'Période : ',
        'no_data' => 'Aucune donnée disponible',
        'date_filter' => 'Filtrer par plage de dates',
        'start_date' => 'Date de début',
        'end_date' => 'Date de fin',
        'filter_submit' => 'Appliquer le filtre de dates',
    ],

    'empty' => [
        'matches_history' => [
            Pronouns::FEMININE => "Cette joueuse n'a pas de matches à son actif",
            Pronouns::MASCULINE => "Ce joueur n'a pas de matches à son actif",
            Pronouns::NEUTRAL => "Cette personne n'a pas de matches à son actif",
        ],
        'players_history' => [
            Pronouns::FEMININE => "Cette joueuse n'a pas d'équipe",
            Pronouns::MASCULINE => "Ce joueur n'a pas d'équipe",
            Pronouns::NEUTRAL => "Cette personne n'a pas d'équipe",
        ],
    ],

    'errors' => [
        'multiple_active_teams' => "Une joueuse ne peut avoir qu'une seule équipe active à la fois.",
    ],

    'nav' => [
        'aria_label' => [
            Pronouns::FEMININE => 'Navigation de la joueuse',
            Pronouns::MASCULINE => 'Navigation du joueur',
            Pronouns::NEUTRAL => 'Navigation du joueur·se',
        ],
        'overview' => "Vue d'ensemble",
        'matches' => 'Matchs',
        'stats' => 'Statistiques',
        'teams_history' => 'Historique des équipes',
    ],

    'edit' => [
        'title' => 'Modifier la joueuse',
        'logo' => [
            'title' => 'Photo',
            'submit' => 'Téléverser',
            'history_title' => 'Historique des photos',
            'history_from' => 'Depuis',
            'history_until' => "Jusqu'à",
            'history_add' => "Ajouter à l'historique",
            'history_remove_confirm' => "Retirer définitivement cette entrée de l'historique des photos ?",
            'history_empty' => 'Aucune photo précédente.',
        ],
        'profile' => [
            'title' => 'Profil',
            'submit' => 'Enregistrer les modifications',
        ],
        'fields' => [
            'handle' => 'Pseudo',
            'first_name' => 'Prénom',
            'last_name' => 'Nom',
            'country_code' => 'Code pays',
            'country_code_search' => 'Rechercher un pays…',
            'country_code_none' => 'Aucun pays / international',
            'pronouns' => 'Pronoms',
            'pronouns_options' => [
                Pronouns::FEMININE => 'Féminin (elle)',
                Pronouns::MASCULINE => 'Masculin (il)',
                Pronouns::NEUTRAL => 'Neutre / inclusif (iel)',
            ],
            'bio' => 'Description',
            'vlr_id' => 'ID VLR.gg',
            'vlr_id_info' => "Non affiché ni partagé publiquement — utilisé en interne pour simplifier notre travail.",
            'liquipedia_link' => 'Lien Liquipedia',
            'is_active' => 'Joueuse active',
            'socials' => 'Réseaux sociaux',
        ],
        'team_history' => [
            'title' => 'Équipe(s) actuelle(s)',
            'history_title' => 'Historique des équipes',
            'add' => 'Ajouter une équipe',
            'remove_confirm' => "Supprimer définitivement cette entrée d'historique pour :team ?",
            'current_empty' => "Pas d'équipe actuellement.",
            'history_empty' => "Aucun historique d'équipe.",
        ],
    ],
];
