<?php

use App\Support\Pronouns;

return [
    'nav' => [
        'aria_label' => 'Navigation du profil',
        'overview' => 'Aperçu',
        'news' => 'Actualités',
    ],
    'profile' => [
        'fan_of' => 'Fan de',
        'view_player_profile' => [
            Pronouns::FEMININE => 'Voir le profil joueuse',
            Pronouns::MASCULINE => 'Voir le profil joueur',
            Pronouns::NEUTRAL => 'Voir le profil joueur·se',
        ],
        'global_roles_title' => 'Rôles globaux',
        'no_roles' => 'Aucun.',
    ],
    'news' => [
        'written_by' => 'Articles rédigés par :name.',
        'lang_label' => 'Langue',
        'lang_all' => 'Toutes les langues',
        'from_label' => 'Du',
        'until_label' => 'Au',
        'filter_submit' => 'Filtrer',
        'filter_reset' => 'Réinitialiser',
    ],
];
