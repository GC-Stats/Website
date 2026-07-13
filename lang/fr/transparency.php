<?php

return [
    'title' => 'Transparence',
    'subtitle' => 'Comment GC Stats est développé, hébergé et financé',
    'intro' => "GC Stats est un projet communautaire à but non lucratif. Un de mes objectifs était de proposer un projet éthique et transparent, c'est le but de cette page, on dévoile le code source, comment on travaille, les finances, tout.",
    'dev' => [
        'title' => 'Développement',
        'body' => "Le code source de GC Stats et de l'ensemble de nos projets est open source et disponible publiquement sur GitHub. Chacun peut consulter le code, signaler un bug ou proposer une amélioration.<br><br>La décision des ajouts/nouveautés faites sur le site sont faites par le staff après discussion et vote, et en ayant les retours de la communauté, en cas de doute sur des décisions, seront fait des sondages sur le Discord ou sur Twitter pour les décisions majeurs",
        'link' => 'Voir le code source',
    ],
    'hosting' => [
        'title' => 'Hébergement & Infrastructure',
        'body' => 'Nous nous appuyons sur quatre prestataires pour garder GC Stats rapide, fiable et transparent. Voici exactement qui héberge quoi, sans jamais revendre les données collectées.',
        'providers' => [
            'cdn' => [
                'name' => 'BunnyCDN',
                'role' => 'Diffusion de contenu',
                'body' => 'Mise en cache de notre CSS/JS, ainsi que des images de joueuses, équipes & tournois, sur des serveurs partout dans le monde, afin d\'aléger nos serveurs. Notre site opendata est également servie par Bunny (data.gc-stats.app)',
            ],
            'servers' => [
                'name' => 'Hetzner',
                'role' => 'Serveurs applicatifs',
                'body' => "Fait tourner les serveurs d'application et de base de données qui font fonctionner le site, l'API et tous nos services (à l'exception du site d'opendata).",
            ],
            'mail' => [
                'name' => 'MXRoute',
                'role' => 'Hébergement mail',
                'body' => "Gère l'envoi et le stockage de nos e-mails.",
            ],
            'domain' => [
                'name' => 'Infomaniak',
                'role' => 'Nom de domaine',
                'body' => 'Registraire suisse gérant notre nom de domaine. Cependant les serveurs de noms sont gérer par Bunny',
            ],
        ],
    ],
    'data' => [
        'title' => 'Données collectées',
        'body' => "Nous documentons publiquement l'ensemble des données que nous stockons : joueurs, équipes, tournois et matchs. Aucune donnée n'est collectée sans raison liée au fonctionnement du site.",
        'link' => 'Voir les données que nous stockons',
    ],
    'finance' => [
        'title' => 'Finances',
        'body' => "GC Stats publie l'intégralité de ses revenus et dépenses dans un livre de comptes public, mis à jour à chaque mouvement.",
        'income' => 'Revenus',
        'expense' => 'Dépenses',
        'balance' => 'Solde',
        'link' => 'Consulter le livre de comptes complet',
        'last_update' => 'Dernière entrée le :date',
        'no_entry' => 'Aucune entrée enregistrée pour le moment.',
    ],
];
