<?php

return [
    'title' => 'Notifications',
    'intro' => 'Everything that has happened on your account, most recent first.',
    'empty' => 'You have no notifications.',
    'mark_all_read' => 'Mark all as read',
    'unread_only' => 'Unread only',
    'show_all' => 'Show all',
    'bell_label' => 'Notifications',
    'see_all' => 'See all notifications',
    'from' => 'From :name',
    'from_system' => 'System',
    'settings_hint' => 'Manage which of these are also sent to your email in your',

    'email' => [
        'view_action' => 'View details',
        'preferences_hint' => 'You are receiving this because you enabled email notifications for this category. Manage your preferences at',
    ],

    'email_preferences' => [
        'title' => 'Email notifications',
        'intro' => 'Choose which of the notifications above should also be sent to your email address.',
        'sanction' => 'Sanctions issued against my account',
        'change_request' => 'Updates on my change requests',
        'social' => 'Social activity',
        'report' => 'Outcome of reports I submitted',
        'submit' => 'Save preferences',
        'saved' => 'Email notification preferences updated.',
    ],

    'sanction_issued' => [
        'title' => 'A sanction was issued against your account',
        'description' => 'A :type was issued against your account. See your account settings for details.',
    ],
    'change_request_comment' => [
        'title' => 'New comment on your change request',
        'description' => ':author: :body',
    ],
    'change_request_accepted' => [
        'title' => 'Your change request was accepted',
        'description' => 'All the proposed changes on your change request were accepted.',
    ],
    'change_request_rejected' => [
        'title' => 'Your change request was rejected',
        'description' => 'Your change request was reviewed and rejected.',
    ],
    'change_request_withdrawn' => [
        'title' => 'Your change request was withdrawn',
        'description' => 'A moderator withdrew your change request.',
    ],
    'report_resolved' => [
        'title' => 'Your report has been reviewed',
        'description' => [
            'actioned' => 'Your report was reviewed and action was taken. Click to see the outcome.',
            'dismissed' => 'Your report was reviewed and dismissed. Click to see the outcome.',
        ],
    ],
];
