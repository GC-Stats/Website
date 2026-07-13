<?php

/**
 * GC-Stats — Purge Bunny CDN cache job
 *
 * Queued job that performs the actual Bunny.net purge API calls for a batch
 * of URLs, off the web request cycle. Failures are logged and swallowed —
 * a CDN purge failure must never fail the business action that triggered it.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PurgeBunnyCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(
        private readonly array $urls,
    ) {}

    public function handle(): void
    {
        $apiKey = config('services.bunny.api_key');

        foreach ($this->urls as $url) {
            $response = Http::withHeaders(['AccessKey' => $apiKey])
                ->timeout(5)
                ->get('https://api.bunny.net/purge', ['url' => $url]);

            if ($response->failed()) {
                Log::warning('Bunny CDN purge failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);
            }
        }
    }
}
