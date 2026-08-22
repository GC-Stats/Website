<?php

use App\Support\Pronouns;

return [
    'nav' => [
        'aria_label' => 'Profile navigation',
        'overview' => 'Overview',
        'news' => 'News',
    ],
    'profile' => [
        'edit_button' => 'Edit my profile',
        'fan_of' => 'Fan of',
        'view_player_profile' => [
            Pronouns::FEMININE => 'View player profile',
            Pronouns::MASCULINE => 'View player profile',
            Pronouns::NEUTRAL => 'View player profile',
        ],
        'global_roles_title' => 'Global roles',
        'no_roles' => 'None.',
        'report' => [
            'trigger' => 'Report this profile',
            'title' => 'Report a user',
            'category_label' => 'Category',
            'reason_label' => 'Reason',
            'submit' => 'Submit report',
            'thanks' => 'Thanks, this has been reported to our moderators.',
        ],
    ],
    'news' => [
        'written_by' => 'Articles written by :name.',
        'lang_label' => 'Language',
        'lang_all' => 'All languages',
        'from_label' => 'From',
        'until_label' => 'Until',
        'filter_submit' => 'Filter',
        'filter_reset' => 'Reset',
    ],
];
