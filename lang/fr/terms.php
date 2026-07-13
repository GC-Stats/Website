<?php

return [
    'title' => 'Conditions d\'utilisation',
    'last_updated' => 'Dernière mise à jour : :date',

    'intro' => 'En accédant à GC Stats ou en utilisant la plateforme, vous acceptez les présentes Conditions d\'utilisation. Veuillez les lire attentivement avant toute utilisation.',

    'service' => [
        'title' => 'À propos du service',
        'text' => 'GC Stats est une plateforme communautaire dédiée à l\'archivage et au partage des données compétitives des tournois Valorant Game Changers. Le service donne accès aux résultats de tournois, statistiques de joueurs, informations d\'équipes et données de matchs. GC Stats n\'est pas affilié à Riot Games, ni approuvé ou officiellement associé à celle-ci.',
    ],

    'access' => [
        'title' => 'Accès & éligibilité',
        'text' => 'La plateforme est accessible au public. Les profils joueurs, les compositions d\'équipes et les statistiques de matchs sont compilés par notre équipe à partir des matchs de tournois suivis publiquement. La liaison ou la modification d\'un profil joueur, lorsqu\'applicable, est gérée directement par notre équipe sur demande et ne nécessite pas d\'authentifier un compte Riot auprès de GC Stats.',
    ],

    'riot_data' => [
        'title' => 'Données de match Riot',
        'text' => 'GC Stats récupère les statistiques de match directement via l\'API officielle de Riot Games pour les parties identifiées dans les tournois suivis. Ces données incluent :',
        'items' => [
            'riot_id' => 'Les Riot ID (nom de jeu et tagline) des joueuses participantes',
            'matchs' => 'L\'historique de matchs pour les parties identifiées dans les tournois suivis',
            'stats' => 'Les statistiques en match : tableau des scores, résumé de performance, résumé économique et kill feed',
        ],
        'opt_in' => 'La participation de base à un match (nom, statistiques) est enregistrée pour toute joueuse apparaissant dans un match de tournoi suivi, car il s\'agit de données compétitives publiques. Les informations de profil supplémentaires (biographie, réseaux sociaux, photo) ne sont ajoutées qu\'avec le consentement de la joueuse ou de son équipe.',
        'correction' => 'Si vous apparaissez dans ces données et souhaitez demander une correction ou une suppression, vous pouvez nous contacter à tout moment — voir notre Politique de confidentialité pour plus de détails.',
    ],

    'prohibited' => [
        'title' => 'Utilisations interdites',
        'text' => 'Vous vous engagez à ne pas utiliser la plateforme ou son API à des fins :',
        'items' => [
            'gambling' => 'De jeux d\'argent, paris ou toute activité impliquant des mises sur des résultats de matchs',
            'misinformation' => 'De diffusion de désinformation, de statistiques falsifiées ou de données manipulées',
            'harassment' => 'De harcèlement, ciblage ou doxing de joueurs ou membres de la communauté',
            'illegal_activity' => 'Illégales au regard du droit applicable',
            'scraping' => 'De scraping automatisé massif au-delà d\'un usage normal de l\'API',
            'reselling' => 'De revente ou redistribution des données brutes en tant que produit autonome',
        ],
    ],

    'api' => [
        'title' => 'Utilisation de l\'API',
        'text' => 'GC Stats met à disposition une API publique destinée aux projets communautaires et GC. Son utilisation est soumise aux présentes Conditions. L\'API est prévue à des fins informatives, analytiques et communautaires. Une limitation de débit peut être appliquée. GC Stats se réserve le droit de révoquer l\'accès à l\'API pour tout usage contraire aux présentes Conditions ou aux politiques développeur de Riot Games.',
    ],

    'ip' => [
        'title' => 'Propriété intellectuelle',
        'text' => 'Les statistiques de jeu et données structurées compilées par GC Stats sont mises à disposition sous licence MIT modifiée, telle que détaillée dans le dépôt GitHub public. Les logos d\'équipes, images de joueurs, assets de tournois et éléments de marque restent la propriété de leurs ayants droit respectifs. GC Stats ne revendique aucun droit sur les assets du jeu, qui sont la propriété de Riot Games.',
    ],

    'liability' => [
        'title' => 'Limitation de responsabilité',
        'text' => 'GC Stats est fourni "en l\'état", sans garantie d\'aucune sorte. Nous ne garantissons pas l\'exactitude, l\'exhaustivité ou la disponibilité des données. GC Stats ne saurait être tenu responsable de tout dommage indirect, accessoire ou consécutif découlant de l\'utilisation de la plateforme. Les statistiques affichées peuvent contenir des erreurs ; vérifiez toujours auprès des sources officielles pour toute décision compétitive.',
    ],

    'changes' => [
        'title' => 'Modifications des présentes Conditions',
        'text' => 'Nous pouvons mettre à jour ces Conditions ponctuellement. La date en haut de cette page reflète la dernière révision. La poursuite de l\'utilisation de la plateforme après la publication des modifications vaut acceptation des Conditions mises à jour.',
    ],

    'contact' => [
        'title' => 'Contact',
        'text' => 'Pour toute question relative aux présentes Conditions :',
        'email' => 'contact@gc-stats.app',
    ],

    'riot_notice' => 'GC Stats n\'est pas approuvé par Riot Games et ne reflète pas les opinions de Riot Games ni de toute personne officiellement impliquée dans la production ou la gestion des propriétés Riot Games. Riot Games et toutes les propriétés associées sont des marques commerciales ou déposées de Riot Games, Inc.',
];
