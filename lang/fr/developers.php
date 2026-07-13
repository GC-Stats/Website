<?php

return [
    'title' => 'Documentation Développeur',
    'intro' => 'Toutes nos données sont mise à disposition pour vous aider à construire votre projet ! Et si vous voulez contribuer à GC-Stats, vous pouvez aussi !',

    'api_key' => [
        'title' => 'Récupérer une clé API',
        'body' => 'Pour éviter les abus, notre API nécessite une authentication. Si vous souhaitez l\'utiliser, rendez-vous sur notre Discord et ouvrez un ticket',
        'get_a_key' => 'Demande sa clé',
        'warning' => 'Merci d\'inclure dans votre ticket:',
        'step_1' => 'Le nom & la présentation de votre projet',
        'step_2' => 'L\'utilisation prévue de notre API',
        'step_3' => 'Rate-Limit nécessaire',
        'btn' => 'Ouvrir un ticket sur Discord',
        'forbidden_title' => 'Restrictions Strictes',
        'forbidden_text' => 'Il est strictement interdit de réutiliser ces données sur des plateformes promouvant les paris (gambling), la désinformation, la haine ou toute activité illégale.',
    ],

    'swagger' => [
        'title' => 'Documentation API',
        'body' => 'Nous documentatons nos API Endpoints avec Swagger, vous pouvez trouvez nos routes, structure de requête & réponses sur notre documentation.',
        'btn' => 'Explorer Swagger UI',
    ],

    'dashboard' => [
        'title' => 'Dashboard API',
        'body' => 'Suivez l\'utilisation de votre clé API, les rate limits et les statistiques de requêtes depuis le dashboard API.',
        'btn' => 'Ouvrir le Dashboard API',
    ],

    'opendata' => [
        'title' => 'Portail Open Data',
        'body' => 'Vous préférez explorer ou télécharger directement nos jeux de données plutôt que d\'utiliser l\'API ? Consultez notre portail Open Data.',
        'btn' => 'Visiter le portail Open Data',
    ],

    'git' => [
        'title' => 'Open Source & Contributions',
        'body' => 'On a décider de rendre la majorité du projet opensource, pour laisser la communauté participer et parce que le closed source C\'EST DE LA MERDE. Tous nos repos (à l\'exception de notre dashboard) sont opensource & ouvert aux contributions. Merci de lire nos règles avant de faire une PR.',
        'badge' => 'Lire le CONTRIBUTE.md',
    ],
];
