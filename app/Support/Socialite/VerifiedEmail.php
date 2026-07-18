<?php

/**
 * GC-Stats — Verified email resolver
 *
 * Only Discord's user payload exposes an explicit `verified` flag for the
 * account's email; Twitch and Twitter/X don't return one (both require a
 * confirmed email at the OAuth-consent step, so their email is trusted as
 * given). An unverified Discord email must never be used to create or claim
 * a GC-Stats account — otherwise an attacker could squat a victim's email
 * on a new account before the victim signs up normally.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support\Socialite;

class VerifiedEmail
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function resolve(string $provider, array $raw, ?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        if ($provider === 'discord' && array_key_exists('verified', $raw) && $raw['verified'] !== true) {
            return null;
        }

        return $email;
    }
}
