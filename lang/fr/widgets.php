<?php

return [
    'title' => 'Widgets',
    'intro' => 'Des overlays prêts pour la diffusion, pensés pour un Browser Source. Choisissez vos options ci-dessous et récupérez un lien à intégrer directement dans OBS.',

    'available' => [
        'head_to_head' => [
            'name' => 'Face to Face',
            'description' => 'Radar chart du taux de victoire comparant les mappools de deux équipes, avec un filtre optionnel par patch ou mappool personnalisée.',
        ],
    ],

    'configure' => 'Configurer',
    'no_preview' => 'Aucun aperçu disponible pour le moment',

    'builder' => [
        'team_a' => 'Équipe A',
        'team_b' => 'Équipe B',
        'tournament' => 'Tournoi (optionnel)',
        'tournament_hint' => 'Restreint la comparaison aux matchs joués dans ce tournoi.',
        'start_date' => 'Date de début (optionnel)',
        'end_date' => 'Date de fin (optionnel)',
        'patch' => 'Patch (optionnel)',
        'patch_placeholder' => 'ex. 9.1',
        'patch_hint' => 'Limite la mappool aux maps jouées sous ce patch.',
        'mappool' => 'Mappool personnalisée (optionnel)',
        'mappool_placeholder' => 'ex. Ascent, Bind, Haven',
        'mappool_hint' => 'Noms de maps séparés par des virgules. Prioritaire sur le patch ci-dessus.',
        'submit' => 'Générer le lien',
    ],

    'result' => [
        'title' => 'Votre lien de widget',
        'copy' => 'Copier le lien',
        'copied' => 'Copié !',
        'open' => 'Ouvrir',
        'preview' => 'Aperçu',
        'embed_hint' => 'Dans OBS, ajoutez une source "Navigateur" avec cette URL. Le fond est transparent.',
    ],
];
