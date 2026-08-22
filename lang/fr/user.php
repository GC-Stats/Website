<?php

use App\Support\Pronouns;

return [
    'nav' => [
        'aria_label' => 'Navigation du profil',
        'overview' => 'Aperçu',
        'news' => 'Actualités',
    ],
    'profile' => [
        'edit_button' => 'Modifier mon profil',
        'fan_of' => 'Fan de',
        'view_player_profile' => [
            Pronouns::FEMININE => 'Voir le profil joueuse',
            Pronouns::MASCULINE => 'Voir le profil joueur',
            Pronouns::NEUTRAL => 'Voir le profil joueur·se',
        ],
        'global_roles_title' => 'Rôles globaux',
        'no_roles' => 'Aucun.',
        'report' => [
            'trigger' => 'Signaler ce profil',
            'title' => 'Signaler un utilisateur',
            'category_label' => 'Catégorie',
            'reason_label' => 'Raison',
            'submit' => 'Envoyer le signalement',
            'thanks' => 'Merci, ceci a été signalé à nos modérateurs.',
        ],
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
