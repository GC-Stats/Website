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

    'doc_dashboard' => [
        'title' => 'Dashboard API',
        'body' => 'Suivez l\'utilisation de votre clé API, les rate limits et les statistiques de requêtes depuis le dashboard API.',
        'btn' => 'Ouvrir le Dashboard API',
    ],

    'dashboard' => [
        'title' => 'Vue d\'ensemble',
        'nav' => [
            'title' => 'Développeur',
            'dashboard' => 'Vue d\'ensemble',
            'api-keys' => 'Clés API',
            'requests' => 'Historique',
            'stats' => 'Statistiques',
            'back_to_site' => 'Retour au site',
        ],
        'status' => [
            'api-key-toggled' => 'Statut de la clé mis à jour.',
            'api-key-regenerated' => 'Clé régénérée.',
        ],
        'overview' => [
            'title' => 'Vue d\'ensemble',
            'api-keys' => 'Clés API',
            'requests' => 'Requêtes',
            'avg_response_time' => 'Temps de réponse moyen',
            'error_rate' => 'Taux d\'erreur',
        ],
        'api_keys' => [
            'title' => 'Clés API',
            'search_placeholder' => 'Rechercher par nom',
            '403' => 'Vous ne pouvez pas modifier cette clé.',
            'client_name' => 'Nom',
            'rate_limit' => 'Rate limit',
            'status' => 'Statut',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'empty' => 'Aucune clé API pour le moment.',
            'regenerate' => 'Régénérer',
            'toggled' => 'Statut de la clé mis à jour.',
            'regenerate_confirm' => 'Régénérer cette clé ? La clé actuelle cessera de fonctionner immédiatement.',
            'reveal_banner' => [
                'title' => 'Clé créée',
                'body' => 'Ce lien affiche la clé en clair une seule fois. Elle ne pourra plus être récupérée après.',
                'copy' => 'Copier le lien',
                'copied' => 'Copié !',
            ],
        ],
        'filter' => [
            'key' => 'Clé API',
        ],
        'requests' => [
            'title' => 'Historique des requêtes',
            'when' => 'Date',
            'endpoint' => 'Endpoint',
            'method' => 'Méthode',
            'status' => 'Statut',
            'duration' => 'Durée',
            'empty' => 'Aucune requête trouvée.',
            'filter' => [
                'all_endpoints' => 'Tous les endpoints',
                'all_statuses' => 'Tous les statuts',
                'from' => 'Du',
                'to' => 'Au',
                'submit' => 'Filtrer',
                'reset' => 'Réinitialiser',
            ],
        ],
        'stats' => [
            'title' => 'Statistiques',
            'requests_24h' => 'Requêtes (24h)',
            'requests_7d' => 'Requêtes (7j)',
            'requests_30d' => 'Requêtes (30j)',
            'error_rate' => 'Taux d\'erreur (30j)',
            'chart_title' => 'Requêtes — 30 derniers jours',
            'chart_requests' => 'Requêtes',
            'chart_errors' => 'Erreurs',
            'response_time_title' => 'Temps de réponse (30j)',
            'min' => 'Min',
            'max' => 'Max',
            'p50' => 'Médiane (p50)',
            'p95' => 'p95',
            'p99' => 'p99',
            'top_endpoints_title' => 'Endpoints les plus utilisés',
            'endpoint' => 'Endpoint',
            'requests' => 'Requêtes',
            'avg_response_time' => 'Temps de réponse moyen',
            'error_rate_col' => 'Erreur %',
            'empty' => 'Aucune requête sur les 30 derniers jours.',
        ],
    ],
];
