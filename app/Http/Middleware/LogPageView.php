<?php

/**
 * GC-Stats — Page view logging middleware
 *
 * Records anonymized page view counts (per region/country and path) into a
 * Redis buffer for successful, non-asset GET requests, later persisted by
 * the SyncPageViews command.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use App\Support\Geo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogPageView
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $statusCode = $response->getStatusCode();

        if ($request->isMethod('GET') && $statusCode === 200 && ! $request->expectsJson()) {
            if ($response instanceof BinaryFileResponse) {
                return $response;
            }

            $uri = $request->getPathInfo();

            if (! preg_match('/\.(mejs|css|js|jpg|jpeg|png|gif|svg|ico|woff|woff2|ttf|otf)$/i', $uri)) {
                if (! str_starts_with($uri, '/livewire') && ! str_starts_with($uri, '/health')) {
                    $country = strtoupper($request->header('Cdn-RequestCountryCode', 'UNK'));
                    $region = Geo::regionFromCountry($country);

                    $redisKey = "{$region}|{$country}:{$uri}";
                    Redis::hincrby('page_views_buffer', $redisKey, 1);
                }
            }
        }

        return $response;
    }
}
