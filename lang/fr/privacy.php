<?php

return [
    'title' => 'Politique de Confidentialité',
    'last_updated' => 'Dernière mise à jour : :date',

    'intro' => 'La présente politique vous informe sur la manière dont nous traitons les données sur ce site, dans un souci de transparence et de respect de la vie privée.',

    'analytics' => [
        'title' => 'Navigation & Statistiques',
        'text' => 'Nous mesurons l\'audience du site de manière totalement anonyme : ces statistiques ne stockent aucune adresse IP, uniquement le pays, la page et l\'heure de la visite, sans aucun suivi (tracking) individuel ni empreinte numérique (fingerprinting). Si vous créez un compte, votre session de connexion (adresse IP et user-agent) est en revanche stockée temporairement à des fins de sécurité (protection contre le piratage de compte), comme c\'est le cas pour la quasi-totalité des sites avec authentification.',
    ],

    'public_data' => [
        'title' => 'Données publiques',
        'text' => 'Les informations de profil (pseudo, biographie, réseaux sociaux, pays) sont fournies volontairement par la joueuse et affichées publiquement sur le site. Les statistiques de jeu sont collectées directement via l\'API officielle de Riot Games pour les matchs identifiés dans les tournois suivis. L\'ensemble de ces données est accessible publiquement et partagé via notre API.',
    ],

    'private_data' => [
        'title' => 'Données privées',
        'text' => 'Les données personnelles que nous stockons sont limitées à ce qui est nécessaire au fonctionnement du site et de son système de comptes : un identifiant Discord et un identifiant Riot pour lier un profil de joueuse, ainsi que les données de compte décrites ci-dessous pour les personnes qui créent un compte utilisateur.',
        'discord_usage' => 'Identifiant Discord : Utilisé pour valider l\'identité d\'une joueuse lors de la modification de son profil via notre Discord. Stocké et visible de notre staff uniquement.',
        'riot_usage' => 'Identifiant Riot : Identifié et assigné par notre équipe à partir des données de match récupérées via l\'API officielle de Riot Games. Utilisé uniquement pour associer les statistiques de match à un profil de joueuse. Peut être corrigé ou supprimé sur demande.',
    ],

    'opt_in' => [
        'title' => 'Politique d\'opt-in',
        'text' => 'La participation de base à un match (nom, statistiques) est enregistrée pour toute joueuse apparaissant dans un match de tournoi suivi, car il s\'agit de données compétitives publiques. Les informations de profil supplémentaires (biographie, réseaux sociaux, photo) ne sont ajoutées qu\'avec le consentement de la joueuse ou de son équipe.',
    ],

    'account_data' => [
        'title' => 'Compte utilisateur',
        'text' => 'La création d\'un compte utilisateur (nécessaire pour réagir aux news, gérer un profil de joueuse ou administrer le site) entraîne le stockage d\'une adresse email, d\'un mot de passe (haché, jamais stocké en clair), de vos préférences d\'interface et, si vous les activez, d\'un secret de double authentification (2FA), de codes de récupération, ou de clés d\'accès (passkeys). Ces informations ne sont jamais partagées ni rendues publiques.',
    ],

    'social_login' => [
        'title' => 'Connexion via Discord, Twitter/X ou Twitch',
        'text' => 'Vous pouvez lier votre compte à Discord, Twitter/X ou Twitch pour vous connecter plus rapidement. Nous stockons alors votre identifiant sur la plateforme concernée, votre pseudo, votre avatar, ainsi que les jetons d\'accès (access/refresh token) nécessaires pour maintenir la connexion — ces jetons ne sont utilisés que pour authentifier votre compte et ne sont jamais partagés. Vous pouvez délier ces comptes à tout moment depuis les paramètres de votre profil.',
        'gravatar' => 'Avatar par défaut : Si vous n\'avez pas d\'avatar via un compte lié, une image peut être demandée à Gravatar (Automattic) à partir d\'une empreinte de votre email, un service tiers standard utilisé par de nombreux sites.',
    ],

    'moderation' => [
        'title' => 'Modération & sécurité de la communauté',
        'text' => 'Pour assurer la sécurité de la communauté, nous conservons un historique des sanctions (avertissements, bannissements) appliquées à un compte ou une équipe, ainsi que des signalements soumis par les utilisatrices (fraude, contournement de bannissement, harcèlement, faux compte, etc.) et un journal d\'activité des actions de modération.',
        'retention_note' => 'Afin d\'empêcher le contournement d\'un bannissement, une empreinte technique (telle qu\'une adresse email) peut être associée à une sanction et conservée même après la suppression du compte concerné. Il en va de même pour l\'historique des signalements et le journal de modération, qui constituent un dossier de sécurité conservé indépendamment du compte.',
    ],

    'data_structure' => [
        'title' => 'Structure des données',
        'text' => 'Pour mieux comprendre les données que nous stockons et partageons, vous pouvez retrouver une structure de nos données ci-dessous. Elle liste toutes les données stockées, explique leur utilité, si elles sont obligatoires, et si elles sont partagées.',
        'button' => 'Voir les données stockées',
    ],

    'retention' => [
        'title' => 'Conservation des données',
        'text' => 'Les données de jeu sont conservées tant que l\'équipe ou la joueuse est active dans l\'écosystème du projet, et les données de compte tant que celui-ci existe. Vous pouvez demander la suppression de vos données à tout moment, à l\'exception des données strictement nécessaires à un dossier de modération en cours (voir ci-dessus).',
    ],

    'rights' => [
        'title' => 'Vos Droits (RGPD)',
        'text' => 'Conformément au RGPD, vous disposez d\'un droit d\'accès, de rectification et de suppression de vos informations personnelles.',
        'contact' => 'Pour toute demande : gdpr@gc-stats.app',
    ],

    'cookies' => [
        'title' => 'Cookies',
        'text' => 'Ce site n\'utilise aucun cookie publicitaire ou de profilage. Seuls des cookies techniques strictement nécessaires au fonctionnement de la session peuvent être utilisés.',
    ],

    'takedown' => 'Demander un retrait de contenu',
];
