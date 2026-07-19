<?php

/**
 * GC-Stats — Provider account age resolver
 *
 * Resolves the creation date of a linked provider account, per-provider:
 * - Discord: no created_at on the user object, but the id is a Snowflake
 *   that encodes the creation timestamp — decoded locally, no extra call.
 * - Twitch: the Helix /users response already returns created_at (not
 *   mapped by the Socialite provider, but preserved in getRaw()).
 * - Twitter/X: only present because TwitterProviderWithCreatedAt requests
 *   it explicitly via user.fields.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support\Socialite;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class ProviderAccountAge
{
    private const DISCORD_EPOCH_MS = 1420070400000;

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function resolve(string $provider, string $providerId, array $raw): ?CarbonInterface
    {
        return match ($provider) {
            'discord' => self::fromDiscordSnowflake($providerId),
            'twitch', 'twitter' => self::fromRawCreatedAt($raw),
            default => null,
        };
    }

    private static function fromDiscordSnowflake(string $providerId): ?CarbonInterface
    {
        if (! ctype_digit($providerId)) {
            return null;
        }

        $timestampMs = ((int) $providerId >> 22) + self::DISCORD_EPOCH_MS;

        try {
            return Date::createFromTimestampMs($timestampMs);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private static function fromRawCreatedAt(array $raw): ?CarbonInterface
    {
        if (empty($raw['created_at'])) {
            return null;
        }

        try {
            return Date::parse($raw['created_at']);
        } catch (\Throwable) {
            return null;
        }
    }
}
