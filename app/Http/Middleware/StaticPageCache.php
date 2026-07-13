<?php

/**
 * GC-Stats — Static page cache middleware
 *
 * Adds long-lived public Cache-Control headers to responses for static
 * pages, allowing browsers and the CDN to cache them.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StaticPageCache
{
    public function handle(Request $request, Closure $next, int $ttl = 2592000): Response
    {
        $response = $next($request);

        return $response->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}");
    }
}
