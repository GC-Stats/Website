<?php

/**
 * GC-Stats — Giphy service
 *
 * Search/trending backend for the forum composer's GIF picker (see
 * resources/views/livewire/forum-gif-picker.blade.php). Chosen over Tenor —
 * Google shut down the public Tenor API entirely (announced Jan 2026, all
 * keys revoked June 30 2026) — Giphy is the standard replacement other
 * migrated apps (Discord, X, Bluesky) landed on.
 *
 * Every request pins `rating=pg-13`, Giphy's built-in content filter —
 * this blocks NSFW content but there is no separate "hateful content"
 * filter on Giphy's side; `pg-13` plus Giphy's own catalog moderation is
 * the same mitigation every other pg-13-rated integration relies on, not a
 * guarantee. Fails open to an empty result list on any error (same pattern
 * as App\Services\OpenAiModerationService) — a Giphy outage must never
 * break the composer, it just means no GIF results that moment.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class GiphyService
{
    private const BASE_URL = 'https://api.giphy.com/v1/gifs';

    /**
     * Shared across every user — one Giphy API key backs the whole app, so
     * this throttles the outgoing HTTP call itself rather than any one
     * user's usage. Giphy's own Beta-tier key limit is 42 req/min / 1,000
     * req/day (production keys get more, but there's no harm staying under
     * the stricter bound); kept comfortably under that so a burst of
     * composer opens can never trip a 429 / key suspension.
     */
    private const RATE_LIMIT_PER_MINUTE = 30;

    /**
     * @return list<array{id: string, preview_url: string, full_url: string, width: int, height: int}>
     */
    public function search(string $term, int $limit = 24): array
    {
        $term = trim($term);
        $cacheKey = 'giphy:search:'.$limit.':'.md5(Str::lower($term));

        return Cache::remember($cacheKey, now()->addMinutes(5), fn () => $this->request('/search', ['q' => $term, 'limit' => $limit]));
    }

    /**
     * @return list<array{id: string, preview_url: string, full_url: string, width: int, height: int}>
     */
    public function trending(int $limit = 24): array
    {
        return Cache::remember("giphy:trending:{$limit}", now()->addMinutes(10), fn () => $this->request('/trending', ['limit' => $limit]));
    }

    /**
     * @return list<array{id: string, preview_url: string, full_url: string, width: int, height: int}>
     */
    private function request(string $endpoint, array $params): array
    {
        $key = config('services.giphy.key');

        if (! $key) {
            return [];
        }

        if (RateLimiter::tooManyAttempts('giphy-api', self::RATE_LIMIT_PER_MINUTE)) {
            Log::warning('Giphy request throttled by local rate limiter', ['endpoint' => $endpoint]);

            return [];
        }

        RateLimiter::hit('giphy-api', 60);

        try {
            $response = Http::timeout(5)->get(self::BASE_URL.$endpoint, [
                ...$params,
                'api_key' => $key,
                'rating' => 'pg-13',
            ]);

            if ($response->failed()) {
                Log::warning('Giphy request failed', ['endpoint' => $endpoint, 'status' => $response->status()]);

                return [];
            }

            return collect($response->json('data', []))
                ->map(function (array $gif) {
                    $original = $gif['images']['original'] ?? null;
                    $preview = $gif['images']['fixed_width'] ?? $original;

                    if (! $original || ! $preview) {
                        return null;
                    }

                    return [
                        'id' => (string) $gif['id'],
                        'preview_url' => $preview['url'],
                        'full_url' => $original['url'],
                        'width' => (int) $original['width'],
                        'height' => (int) $original['height'],
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}
