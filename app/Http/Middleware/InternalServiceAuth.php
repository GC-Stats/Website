<?php

/**
 * GC-Stats — Internal service authentication middleware
 *
 * Validates HMAC-style signed requests (timestamp + signature headers) from
 * trusted internal services, rejecting expired or unauthenticated requests.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InternalServiceAuth
{
    private const MAX_TIMESTAMP_DRIFT = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('X-Internal-Timestamp');
        $signature = $request->header('X-Internal-Signature');

        if (! $timestamp || ! $signature) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (abs(time() - (int) $timestamp) > self::MAX_TIMESTAMP_DRIFT) {
            return response()->json(['error' => 'Request expired'], 401);
        }

        $secret = config('services.internal.secret');

        if (! $secret) {
            Log::error('Internal service secret is not configured');

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $method = $request->method();
        $endpoint = $request->getPathInfo();
        $body = $request->getContent();

        $payload = "{$timestamp}.{$method}.{$endpoint}.{$body}";
        $expected = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Internal auth failed', [
                'service' => $request->header('X-Service-Name'),
                'ip' => $request->ip(),
                'path' => $endpoint,
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
