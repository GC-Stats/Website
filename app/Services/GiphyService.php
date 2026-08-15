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
use Throwable;

class GiphyService
{
    private const BASE_URL = 'https://api.giphy.com/v1/gifs';

    /**
     * @return list<array{id: string, preview_url: string, full_url: string, width: int, height: int}>
     */
    public function search(string $term, int $limit = 24): array
    {
        return $this->request('/search', ['q' => $term, 'limit' => $limit]);
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
