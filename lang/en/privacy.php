<?php

return [
    'title' => 'Privacy Policy',
    'last_updated' => 'Last updated: :date',

    'intro' => 'This policy informs you about how we handle data on this site, with a focus on transparency and respect for privacy.',

    'analytics' => [
        'title' => 'Navigation & Statistics',
        'text' => 'We measure site audience in a completely anonymous way: these statistics never store an IP address, only the country, the page, and the time of visit, with no individual tracking or digital fingerprinting. If you create an account, your login session (IP address and user agent) is however stored temporarily for security purposes (protection against account takeover), as is standard for virtually every site with authentication.',
    ],

    'public_data' => [
        'title' => 'Public Data',
        'text' => 'Profile information (handle, biography, social links, country) is voluntarily provided by the player and publicly displayed on the site. Match statistics are collected directly via the official Riot Games API for matches identified as part of tracked tournaments. All of this data is publicly accessible and shared through our API.',
    ],

    'private_data' => [
        'title' => 'Private Data',
        'text' => 'The personal data we store is limited to what is needed to run the site and its account system: a Discord ID and a Riot ID to link a player profile, plus the account data described below for anyone who creates a user account.',
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

    'account_data' => [
        'title' => 'User Account',
        'text' => 'Creating a user account (needed to react to news, manage a player profile, or administer the site) stores an email address, a password (hashed, never stored in plain text), your interface preferences, and, if you enable them, a two-factor authentication (2FA) secret, recovery codes, or passkeys. This information is never shared or made public.',
    ],

    'social_login' => [
        'title' => 'Login via Discord, Twitter/X, or Twitch',
        'text' => 'You can link your account to Discord, Twitter/X, or Twitch to sign in faster. We then store your ID on that platform, your nickname, your avatar, and the access/refresh tokens needed to keep the connection alive — these tokens are only used to authenticate your account and are never shared. You can unlink these accounts at any time from your profile settings.',
        'gravatar' => 'Default avatar: If you don\'t have an avatar via a linked account, an image may be requested from Gravatar (Automattic) based on a hash of your email, a standard third-party service used by many websites.',
    ],

    'moderation' => [
        'title' => 'Moderation & Community Safety',
        'text' => 'To keep the community safe, we keep a history of sanctions (warnings, bans) applied to an account or team, reports submitted by users (fraud, ban evasion, harassment, fake accounts, etc.), and an activity log of moderation actions.',
        'retention_note' => 'To prevent ban evasion, a technical fingerprint (such as an email address) may be attached to a sanction and kept even after the associated account is deleted. The same applies to the report history and moderation log, which form a safety record kept independently of the account.',
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
