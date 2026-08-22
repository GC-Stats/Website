<?php

/**
 * GC-Stats — Twitch Helix service
 *
 * Thin wrapper around Twitch's Helix API using an app access token (client
 * credentials grant — config('services.twitch'), the same client already
 * used for Socialite login, no user involved here). Used to detect Player
 * POV streams during live matches — see App\Console\Commands\
 * ScheduledCommand\DetectPlayerPovStreams.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwitchService
{
    private const TOKEN_CACHE_KEY = 'twitch_app_access_token';

    private const MAX_LOGINS_PER_REQUEST = 100;

    /**
     * Currently live streams among the given Twitch logins, keyed by
     * lowercased login. Each entry carries 'title' and 'url'. Chunked at
     * Helix's 100-logins-per-request limit; logins are deduplicated first.
     *
     * @param  list<string>  $logins
     * @return Collection<string, array{title: string, url: string}>
     */
    public function getLiveStreams(array $logins): Collection
    {
        $logins = collect($logins)
            ->filter()
            ->map(fn ($login) => Str::lower(trim($login)))
            ->unique()
            ->values();

        if ($logins->isEmpty()) {
            return collect();
        }

        $token = $this->getAppAccessToken();

        if (! $token) {
            return collect();
        }

        $results = collect();

        foreach ($logins->chunk(self::MAX_LOGINS_PER_REQUEST) as $chunk) {
            $response = Http::withHeaders([
                'Client-Id' => config('services.twitch.client_id'),
                'Authorization' => "Bearer {$token}",
            ])->get('https://api.twitch.tv/helix/streams', [
                'user_login' => $chunk->values()->all(),
                'first' => 100,
            ]);

            if (! $response->successful()) {
                Log::warning('Twitch Helix get-streams call failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                continue;
            }

            foreach ($response->json('data', []) as $stream) {
                $login = Str::lower($stream['user_login'] ?? '');

                if ($login === '') {
                    continue;
                }

                $results->put($login, [
                    'title' => $stream['title'] ?? '',
                    'url' => "https://twitch.tv/{$login}",
                ]);
            }
        }

        return $results;
    }

    /**
     * App access token via the client credentials grant, cached for a bit
     * under the token's own lifetime (Twitch app tokens are typically valid
     * ~60 days, but we refresh well before that to avoid ever using a
     * revoked/expired one).
     */
    private function getAppAccessToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if ($cached) {
            return $cached;
        }

        $response = Http::asForm()->post('https://id.twitch.tv/oauth2/token', [
            'client_id' => config('services.twitch.client_id'),
            'client_secret' => config('services.twitch.client_secret'),
            'grant_type' => 'client_credentials',
        ]);

        if (! $response->successful()) {
            Log::warning('Twitch app access token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = $response->json('access_token');

        // Cached well under the token's real lifetime (Twitch app tokens
        // typically last ~60 days) so we never risk using an expired one.
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addDays(7));

        return $token;
    }
}
