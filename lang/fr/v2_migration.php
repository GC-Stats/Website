<?php

return [
    'title' => 'Migration vers la V2',
    'description' => 'GC Stats se prépare au déploiement de la V2 : certaines fonctionnalités sont temporairement limitées.',
    'intro' => 'GC Stats se prépare au déploiement de sa V2. Pour migrer les données en toute sécurité, une maintenance est planifiée et certaines fonctionnalités seront temporairement limitées.',
    'schedule' => [
        'title' => 'Date de la maintenance',
        'starts' => 'Début de la maintenance',
        'note' => 'Date et heure affichées dans votre fuseau horaire.',
    ],
    'limited' => [
        'title' => 'Fonctionnalités limitées',
        'intro' => 'Pendant la migration, il sera impossible de :',
        'items' => [
            'reactions' => 'Ajouter, retirer ou signaler des réactions',
            'forum' => 'Créer un sujet ou répondre sur le forum',
            'change_requests' => 'Soumettre une demande de modification (joueuse ou équipe) ou y répondre',
            'reports' => 'Signaler un utilisateur',
        ],
    ],
    'available' => [
        'title' => 'Ce qui reste disponible',
        'body' => 'Le site reste entièrement consultable : tournois, matchs, équipes, joueuses, statistiques et forum en lecture. Votre compte et vos paramètres restent accessibles.',
    ],
    'thanks' => 'Merci pour votre patience, on se retrouve très vite sur la V2 !',
];
