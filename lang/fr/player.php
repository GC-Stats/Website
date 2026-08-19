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
    'also_known_as' => 'Également connu sous :',
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
        'all_time' => 'Tout',
        'last_30_days' => '30J',
        'last_60_days' => '60J',
        'insights' => [
            'title' => 'Meilleurs agents',
            'top_acs' => 'Meilleur ACS',
            'top_adr' => 'Meilleur ADR',
            'top_kast' => 'Meilleur KAST',
            'top_entries' => 'Plus de first kills',
            'top_utility' => "Plus de kills à l'utilitaire",
            'top_clutch_rate' => 'Meilleur taux de clutch',
            'top_operator' => "Plus de kills à l'Operator",
            'top_sheriff' => 'Plus de kills au Sheriff',
        ],
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

    'change_request' => [
        'trigger' => 'Suggérer une modification',
        'title' => 'Suggérer une modification — :player',
        'intro' => 'Ne remplissez que les champs à modifier — le reste est laissé tel quel. Un membre du staff vérifie chaque demande avant application.',
        'profile_section' => 'Profil',
        'socials_section' => 'Réseaux sociaux',
        'socials_help' => "Indiquez le pseudo, pas un lien complet — sauf pour Discord, qui n'a pas de lien de profil.",
        'socials_placeholder_username' => 'pseudo',
        'socials_placeholder_discord' => 'pseudo ou lien d\'invitation',
        'photo_section' => 'Photo',
        'photo_label' => 'Nouvelle photo',
        'photo_current' => 'Photo actuelle',
        'photo_help' => 'JPG, PNG ou WebP, 4 Mo max.',
        'team_section' => 'Équipe',
        'current_team' => 'Équipe actuelle : :team',
        'no_current_team' => "Pas d'équipe actuellement.",
        'new_team_label' => 'Nouvelle équipe',
        'history_section' => "Historique d'équipes",
        'history_intro' => "Corrigez le rôle ou les dates d'une entrée existante — pour ajouter une nouvelle équipe, utilisez la section « Équipe » ci-dessus.",
        'history_empty' => "Aucun historique d'équipe.",
        'history_left_at' => 'Date de départ (laisser vide si toujours dans l\'équipe)',
        'link_section' => 'Est-ce vous ?',
        'note_section' => 'Note pour le vérificateur',
        'note_label' => 'Note pour le vérificateur (optionnel)',
        'note_placeholder' => 'Contexte supplémentaire — une source, la raison du changement…',
        'link_to_me_label' => "C'est moi — lier ce profil à mon compte",
        'link_to_me_note' => 'Soumis à validation du staff, comme toute autre modification ici.',
        'submit' => 'Envoyer la demande',
        'submitted_status' => 'Merci — votre demande a été envoyée pour vérification.',
        'errors' => [
            'no_changes' => 'Modifiez au moins un champ, ou cochez "c\'est moi", avant d\'envoyer.',
            'joined_at_required' => 'Choisissez une date d\'arrivée pour le changement d\'équipe proposé.',
            'already_linked_to_you' => 'Ce profil est déjà lié à votre compte.',
            'player_already_linked' => 'Ce profil est déjà lié à un autre compte.',
            'user_already_linked' => 'Votre compte est déjà lié à un autre profil de joueuse.',
        ],
    ],

    'edit' => [
        'title' => 'Modifier la joueuse',
        'logo' => [
            'title' => 'Photo',
            'submit' => 'Téléverser',
            'history_title' => 'Historique des photos',
            'history_from' => 'Depuis',
            'history_until' => "Jusqu'à",
            'history_visible' => 'Afficher sur les anciens matchs',
            'history_add' => "Ajouter à l'historique",
            'history_remove_confirm' => "Retirer définitivement cette entrée de l'historique des photos ?",
            'history_empty' => 'Aucune photo précédente.',
        ],
        'profile' => [
            'title' => 'Profil',
            'submit' => 'Enregistrer les modifications',
        ],
        'aliases' => [
            'title' => 'Alias',
            'body' => 'Autres noms sous lesquels ce joueur est aussi connu, affichés sur son profil public.',
            'placeholder' => 'ex : un ancien pseudo',
            'add' => 'Ajouter un alias',
            'remove' => 'Supprimer',
            'submit' => 'Enregistrer les alias',
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
            'vlr_id_info' => 'Non affiché ni partagé publiquement — utilisé en interne pour simplifier notre travail.',
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
