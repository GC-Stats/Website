<?php

return [
    'title' => 'Politique de Confidentialité',
    'last_updated' => 'Dernière mise à jour : :date',

    'intro' => 'La présente politique vous informe sur la manière dont nous traitons les données sur ce site, dans un souci de transparence et de respect de la vie privée.',

    'analytics' => [
        'title' => 'Navigation & Statistiques',
        'text' => 'Nous mesurons l\'audience du site de manière totalement anonyme. Aucune adresse IP n\'est collectée, stockée ou transmise à des tiers. Nous ne pratiquons aucun suivi (tracking) individuel ni empreinte numérique (fingerprinting). Nous stockons uniquement le pays, la page et l\'heure de la visite.',
    ],

    'public_data' => [
        'title' => 'Données publiques',
        'text' => 'Les informations de profil (pseudo, biographie, réseaux sociaux, pays) sont fournies volontairement par la joueuse et affichées publiquement sur le site. Les statistiques de jeu sont collectées directement via l\'API officielle de Riot Games pour les matchs identifiés dans les tournois suivis. L\'ensemble de ces données est accessible publiquement et partagé via notre API.',
    ],

    'private_data' => [
        'title' => 'Données privées',
        'text' => 'Nous ne stockons quasiment aucune donnée personnelle. Les seuls identifiants personnels stockés sont un identifiant Discord et un identifiant Riot, tous deux fournis volontairement par la joueuse.',
        'discord_usage' => 'Identifiant Discord : Utilisé pour valider l\'identité d\'une joueuse lors de la modification de son profil via notre Discord. Stocké et visible de notre staff uniquement.',
        'riot_usage' => 'Identifiant Riot : Identifié et assigné par notre équipe à partir des données de match récupérées via l\'API officielle de Riot Games. Utilisé uniquement pour associer les statistiques de match à un profil de joueuse. Peut être corrigé ou supprimé sur demande.',
    ],

    'opt_in' => [
        'title' => 'Politique d\'opt-in',
        'text' => 'La participation de base à un match (nom, statistiques) est enregistrée pour toute joueuse apparaissant dans un match de tournoi suivi, car il s\'agit de données compétitives publiques. Les informations de profil supplémentaires (biographie, réseaux sociaux, photo) ne sont ajoutées qu\'avec le consentement de la joueuse ou de son équipe.',
    ],

    'data_structure' => [
        'title' => 'Structure des données',
        'text' => 'Pour mieux comprendre les données que nous stockons et partageons, vous pouvez retrouver une structure de nos données ci-dessous. Elle liste toutes les données stockées, explique leur utilité, si elles sont obligatoires, et si elles sont partagées.',
        'button' => 'Voir les données stockées',
    ],

    'retention' => [
        'title' => 'Conservation des données',
        'text' => 'Les données de jeu sont conservées tant que l\'équipe ou la joueuse est active dans l\'écosystème du projet. Vous pouvez demander la suppression de vos données à tout moment.',
    ],

    'rights' => [
        'title' => 'Vos Droits (RGPD)',
        'text' => 'Conformément au RGPD, vous disposez d\'un droit d\'accès, de rectification et de suppression de vos informations personnelles.',
        'contact' => 'Pour toute demande : gpdr@gc-stats.app',
    ],

    'cookies' => [
        'title' => 'Cookies',
        'text' => 'Ce site n\'utilise aucun cookie publicitaire ou de profilage. Seuls des cookies techniques strictement nécessaires au fonctionnement de la session peuvent être utilisés.',
    ],

    'takedown' => 'Demander un retrait de contenu',
];
