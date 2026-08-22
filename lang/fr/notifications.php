<?php

return [
    'title' => 'Notifications',
    'intro' => 'Tout ce qui s\'est passé sur votre compte, du plus récent au plus ancien.',
    'empty' => 'Vous n\'avez aucune notification.',
    'mark_all_read' => 'Tout marquer comme lu',
    'unread_only' => 'Non lues uniquement',
    'show_all' => 'Tout afficher',
    'bell_label' => 'Notifications',
    'see_all' => 'Voir toutes les notifications',
    'from' => 'De :name',
    'from_system' => 'Système',
    'settings_hint' => 'Gérez lesquelles sont aussi envoyées par email dans vos',

    'email' => [
        'view_action' => 'Voir les détails',
        'preferences_hint' => 'Vous recevez cet email car vous avez activé les notifications par email pour cette catégorie. Gérez vos préférences sur',
    ],

    'email_preferences' => [
        'title' => 'Notifications par email',
        'intro' => 'Choisissez lesquelles des notifications ci-dessus doivent aussi vous être envoyées par email.',
        'sanction' => 'Sanctions émises contre mon compte',
        'change_request' => 'Mises à jour de mes demandes de modification',
        'social' => 'Activité sociale',
        'report' => 'Réponse à mes signalements',
        'submit' => 'Enregistrer les préférences',
        'saved' => 'Préférences de notifications par email mises à jour.',
    ],

    'sanction_issued' => [
        'title' => 'Une sanction a été émise contre votre compte',
        'description' => 'Un(e) :type a été émis(e) contre votre compte. Consultez les paramètres de votre compte pour plus de détails.',
    ],
    'change_request_comment' => [
        'title' => 'Nouveau commentaire sur votre demande de modification',
        'description' => ':author : :body',
    ],
    'change_request_accepted' => [
        'title' => 'Votre demande de modification a été acceptée',
        'description' => 'Toutes les modifications proposées dans votre demande ont été acceptées.',
    ],
    'change_request_rejected' => [
        'title' => 'Votre demande de modification a été refusée',
        'description' => 'Votre demande de modification a été examinée et refusée.',
    ],
    'change_request_withdrawn' => [
        'title' => 'Votre demande de modification a été retirée',
        'description' => 'Un modérateur a retiré votre demande de modification.',
    ],
    'report_resolved' => [
        'title' => 'Votre signalement a été traité',
        'description' => [
            'actioned' => 'Votre signalement a été examiné et une action a été prise. Cliquez pour voir le résultat.',
            'dismissed' => 'Votre signalement a été examiné et classé sans suite. Cliquez pour voir le résultat.',
        ],
    ],
];
