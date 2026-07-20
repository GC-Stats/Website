<?php

/**
 * GC-Stats — EUR/USD exchange rate
 *
 * Used by Admin\FinanceController to convert a single entered amount into
 * both currencies stored on FinanceEntry. Cached 6h so entry creation never
 * waits on frankfurter.app; a failed lookup falls back to a 1:1 rate rather
 * than blocking the write.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRate
{
    public static function eurToUsd(): float
    {
        $cached = Cache::get('exchange-rate:eur-usd');

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)->get('https://api.frankfurter.app/latest', [
                'from' => 'EUR',
                'to' => 'USD',
            ]);
        } catch (ConnectionException $e) {
            Log::warning('ExchangeRate: frankfurter.app lookup failed', ['exception' => $e->getMessage()]);

            return 1.0;
        }

        if (! $response->successful()) {
            return 1.0;
        }

        $rate = (float) $response->json('rates.USD');
        Cache::put('exchange-rate:eur-usd', $rate, now()->addHours(6));

        return $rate;
    }
}
