<?php

use App\Support\Pronouns;

return [
    'title' => [
        'index' => ':staff',
    ],

    'teams' => 'Équipes actuelles',
    'organizations' => 'Organisations actuelles',
    'old_teams' => 'Anciennes équipes',
    'old_organizations' => 'Anciennes organisations',
    'seemore' => 'Voir plus',
    'also_plays_as' => 'Joue également en tant que :player',

    'nav' => [
        'aria_label' => 'Sections du staff',
        'overview' => 'Aperçu',
        'experience' => 'Expérience',
    ],

    'experience' => [
        'title' => ':staff — Expérience',
        'title_role' => ':staff — :role',
        'heading' => 'Expérience',
        'since' => 'Depuis :year',
        'count' => ':count participation|:count participations',
        'empty' => 'Aucune expérience déclarée pour le moment.',
        'representing' => 'Représentait :team',
        'clear_filter' => 'Tous les rôles',
        'unknown_tournament' => 'Tournoi inconnu',

        'career' => [
            'tournaments' => ':count tournoi couvert|:count tournois couverts',
            'roles' => ':count rôle occupé|:count rôles occupés',
            'since' => 'Depuis :year',
        ],

        'stats' => [
            'total' => 'Participations',
            'tournaments' => 'Tournois',
            'matches' => 'Matchs individuels',
            'represented_team' => 'Équipes représentées',
            'represented_org' => 'Organisations représentées',
            'active' => 'Actif',
            'active_range' => ':first – :last',
            'active_single_year' => ':year',
            'categories_heading' => 'Par type de tournoi',
            'languages_heading' => 'Par langue de cast',
        ],
    ],

    'roster' => [
        'roles' => [
            'team' => [
                'coach' => 'Coach',
                'assistant coach' => 'Coach assistant',
                'performance coach' => 'Coach performance',
                'team manager' => "Manager d'équipe",
            ],
            'org' => [
                'manager' => 'Manager',
                'president' => [
                    Pronouns::FEMININE => 'Présidente',
                    Pronouns::MASCULINE => 'Président',
                    Pronouns::NEUTRAL => 'Président·e',
                ],
                'ceo' => 'CEO',
                'vice president' => [
                    Pronouns::FEMININE => 'Vice-présidente',
                    Pronouns::MASCULINE => 'Vice-président',
                    Pronouns::NEUTRAL => 'Vice-président·e',
                ],
                'treasurer' => [
                    Pronouns::FEMININE => 'Trésorière',
                    Pronouns::MASCULINE => 'Trésorier',
                    Pronouns::NEUTRAL => 'Trésorier·ère',
                ],
                'owner' => 'Propriétaire',
                'general manager' => [
                    Pronouns::FEMININE => 'Directrice générale',
                    Pronouns::MASCULINE => 'Directeur général',
                    Pronouns::NEUTRAL => 'Directeur·rice général·e',
                ],
                'talent manager' => 'Talent Manager',
                'content manager' => 'Content Manager',
                'community manager' => 'Community Manager',
                'social media manager' => 'Social Media Manager',
                'graphic designer' => 'Graphiste',
                'video editor' => [
                    Pronouns::FEMININE => 'Monteuse vidéo',
                    Pronouns::MASCULINE => 'Monteur vidéo',
                    Pronouns::NEUTRAL => 'Monteur·se vidéo',
                ],
                'photographer' => 'Photographe',
                'web developer' => [
                    Pronouns::FEMININE => 'Développeuse web',
                    Pronouns::MASCULINE => 'Développeur web',
                    Pronouns::NEUTRAL => 'Développeur·se web',
                ],
                'tournament organizer' => [
                    Pronouns::FEMININE => 'Organisatrice de tournoi',
                    Pronouns::MASCULINE => 'Organisateur de tournoi',
                    Pronouns::NEUTRAL => 'Organisateur·rice de tournoi',
                ],
                'caster' => 'Caster',
                'observer' => 'Observer',
                'host' => [
                    Pronouns::FEMININE => 'Animatrice',
                    Pronouns::MASCULINE => 'Animateur',
                    Pronouns::NEUTRAL => 'Animateur·rice',
                ],
                'production' => 'Production',
                'producer' => [
                    Pronouns::FEMININE => 'Productrice',
                    Pronouns::MASCULINE => 'Producteur',
                    Pronouns::NEUTRAL => 'Producteur·rice',
                ],
                'director' => [
                    Pronouns::FEMININE => 'Directrice',
                    Pronouns::MASCULINE => 'Directeur',
                    Pronouns::NEUTRAL => 'Directeur·rice',
                ],
                'analyst' => 'Analyste',
                'partnerships manager' => 'Responsable partenariats',
                'marketing manager' => 'Responsable marketing',
                'hr' => 'RH',
                'finance' => 'Finance',
            ],
        ],
    ],

    'empty' => [
        'teams' => 'Pas actuellement affilié(e) à une équipe.',
        'organizations' => 'Pas actuellement affilié(e) à une organisation.',
    ],
];
