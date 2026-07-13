<?php

return [
    'title' => 'Privacy Policy',
    'last_updated' => 'Last updated: :date',

    'intro' => 'This policy informs you about how we handle data on this site, with a focus on transparency and respect for privacy.',

    'analytics' => [
        'title' => 'Navigation & Statistics',
        'text' => 'We measure site audience in a completely anonymous way. No IP addresses are collected, stored, or transmitted to third parties. We do not engage in individual tracking or digital fingerprinting. We only store the country, the page & time of visit.',
    ],

    'public_data' => [
        'title' => 'Public Data',
        'text' => 'Profile information (handle, biography, social links, country) is voluntarily provided by the player and publicly displayed on the site. Match statistics are collected directly via the official Riot Games API for matches identified as part of tracked tournaments. All of this data is publicly accessible and shared through our API.',
    ],

    'private_data' => [
        'title' => 'Private Data',
        'text' => 'We store almost no personal data. The only personal identifiers stored
               are a Discord ID and a Riot ID, both provided voluntarily by the player.',
        'discord_usage' => 'Discord ID: Used to validate a player\'s identity when
                        modifying their profile via our Discord.',
        'riot_usage' => 'Riot ID: Identified and assigned by our team from match data
                     retrieved through the official Riot Games API. Used solely
                     to associate match statistics with a player profile.
                     Can be corrected or removed upon request.',
    ],

    'opt_in' => [
        'title' => 'Opt-in Policy',
        'text' => 'Basic match participation (name, statistics) is recorded for any
               player appearing in a tracked tournament match, as this is public
               competitive data. Additional profile information (biography,
               social links, photo) is only added with the player\'s or team\'s
               consent.',
    ],

    'data_structure' => [
        'title' => 'Data Structure',
        'text' => 'To better understand the data we store and share, you can find our data structure below. It lists all stored data, explains their utility, if they are mandatory, and if they are shared.',
        'button' => 'View stored data',
    ],

    'retention' => [
        'title' => 'Data Retention',
        'text' => 'Game data is kept as long as the team or player is active in the project ecosystem. You can request the deletion of your data at any time.',
    ],

    'rights' => [
        'title' => 'Your Rights (GDPR)',
        'text' => 'In accordance with GDPR, you have the right to access, rectify, and delete your personal information.',
        'contact' => 'For any request: gpdr@gc-stats.app',
    ],

    'cookies' => [
        'title' => 'Cookies',
        'text' => 'This site does not use any advertising or profiling cookies. Only strictly necessary technical cookies for session operation may be used.',
    ],

    'takedown' => 'Request a content removal',
];
