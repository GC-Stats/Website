<?php

use App\Support\Pronouns;

return [
    'title' => [
        'index' => ':organization',
    ],

    'staff' => 'Staff actuel',
    'old_staff' => 'Ancien staff',
    'seemore' => 'Voir plus',
    'manage_link' => 'Gérer cette organisation',

    'nav' => [
        'aria_label' => 'Sections de l\'organisation',
        'overview' => 'Aperçu',
        'experience' => 'Expérience',
        'news' => 'Actualités',
    ],

    'experience' => [
        'heading' => 'Expérience',
        'empty' => 'Aucune expérience déclarée pour le moment.',
        'unknown_tournament' => 'Tournoi inconnu',
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
        'staff' => 'Aucun membre du staff listé.',
    ],

    'dashboard' => [
        'nav' => [
            'overview' => 'Aperçu',
            'group_news' => 'Actualités',
            'group_production' => 'Production',
            'group_administration' => 'Administration',
            'news' => 'Actualités',
            'news_media' => 'Médias',
            'news_author' => 'Profil auteur',
            'streams' => 'Streams',
            'vods' => 'VODs',
            'experience' => 'Expérience',
            'roles' => 'Rôles',
            'api_keys' => 'Clés API',
            'public_page' => 'Page publique',
            'back_to_site' => 'Retour au site',
        ],
        'edit' => [
            'title' => 'Modifier',
        ],
        'stats' => [
            'current_staff' => 'Staff actuel',
            'former_staff' => 'Ancien staff',
            'roles' => 'Rôles',
        ],
        'profile' => [
            'title' => 'Profil',
            'submit' => 'Enregistrer le profil',
            'no_permission' => 'Vous n\'avez pas la permission de modifier le profil de cette organisation.',
        ],
        'logo' => [
            'title' => 'Logo',
            'submit' => 'Enregistrer le logo',
        ],
    ],
];
