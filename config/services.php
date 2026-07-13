<?php

/**
 * GC-Stats — Third-party services configuration
 *
 * Standard Laravel services config, extended with credentials for
 * GC-Stats' external integrations (HenrikDev/Riot API, Bunny CDN,
 * internal service authentication).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'bunny' => [
        'api_key' => env('BUNNY_API_KEY') ?? '',
        'pull_zone_url' => env('BUNNY_PULL_ZONE_URL'),
    ],

    'internal' => [
        'secret' => env('INTERNAL_API_SECRET'),
    ],

    'liquipedia' => [
        'user_agent' => env('LIQUIPEDIA_USERAGENT') ?? null,
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'henrikdev' => [
        'key' => env('HENRIKDEV_API_KEY'),
    ],

    'riot' => [
        'key' => env('RIOT_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
