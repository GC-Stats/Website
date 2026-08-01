<?php

use App\Support\Pronouns;

return [
    'nav' => [
        'aria_label' => 'Profile navigation',
        'overview' => 'Overview',
        'news' => 'News',
    ],
    'profile' => [
        'fan_of' => 'Fan of',
        'view_player_profile' => [
            Pronouns::FEMININE => 'View player profile',
            Pronouns::MASCULINE => 'View player profile',
            Pronouns::NEUTRAL => 'View player profile',
        ],
        'global_roles_title' => 'Global roles',
        'no_roles' => 'None.',
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
