<?php

/**
 * GC-Stats — Twitter/X Socialite provider with account creation date
 *
 * Socialite's built-in TwitterProvider only requests
 * `profile_image_url,confirmed_email` in `user.fields`, so the account
 * creation date the X API v2 `/2/users/me` endpoint can return is never
 * fetched. This override adds `created_at` to that field list so
 * ProviderAccountAge can read it back from getRaw().
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support\Socialite;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\TwitterProvider;

class TwitterProviderWithCreatedAt extends TwitterProvider
{
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.twitter.com/2/users/me', [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$token],
            RequestOptions::QUERY => ['user.fields' => 'profile_image_url,confirmed_email,created_at'],
        ]);

        return Arr::get(json_decode($response->getBody(), true), 'data');
    }
}
