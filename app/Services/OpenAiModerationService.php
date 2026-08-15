<?php

/**
 * GC-Stats — OpenAI moderation service
 *
 * Calls OpenAI's free moderation endpoint — the forum's sole auto-
 * moderation signal, see App\Jobs\ModerateForumMessage, the only caller
 * (dispatched via dispatchAfterResponse(), so this never delays a post
 * being visible — see App\Services\ForumService).
 * Fails open: a network error or non-2xx response is logged and treated as
 * "not flagged", since an outage on OpenAI's side must never make forum
 * posting unreliable (nor mass-hide content it never actually reviewed).
 *
 * Genuine successful results are cached by exact text (24h) — cheap
 * insurance against a job retry re-hitting the API for the same input,
 * and against repeated/spam content (a bot posting the same string many
 * times) burning quota on a result we already have. A fail-open result
 * (network error, non-2xx, missing key) is deliberately never cached —
 * caching it would silently keep moderation disabled for that exact text
 * long after a transient OpenAI outage recovered.
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

class OpenAiModerationService
{
    private const CACHE_TTL_HOURS = 24;

    /**
     * @return array{flagged: bool, categories: list<string>}
     */
    public function check(string $text): array
    {
        $key = config('services.openai.key');

        if (! $key) {
            Log::warning('OpenAI moderation check skipped: OPENAI_API_KEY is not configured');

            return ['flagged' => false, 'categories' => []];
        }

        $cacheKey = 'openai_moderation:'.sha1($text);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->callApi($text, $key);

        if ($result !== null) {
            Cache::put($cacheKey, $result, now()->addHours(self::CACHE_TTL_HOURS));

            return $result;
        }

        return ['flagged' => false, 'categories' => []];
    }

    /**
     * Null on any failure — the caller treats that as fail-open, without
     * caching it (see check()).
     *
     * @return ?array{flagged: bool, categories: list<string>}
     */
    private function callApi(string $text, string $key): ?array
    {
        try {
            $response = Http::withToken($key)
                ->timeout(5)
                ->post('https://api.openai.com/v1/moderations', ['input' => $text]);

            if ($response->failed()) {
                Log::warning('OpenAI moderation check failed', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                return null;
            }

            $result = $response->json('results.0', []);

            $categories = collect($result['categories'] ?? [])
                ->filter()
                ->keys()
                ->all();

            return ['flagged' => (bool) ($result['flagged'] ?? false), 'categories' => $categories];
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
