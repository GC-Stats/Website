<?php

/**
 * GC-Stats — Internal API request signer
 *
 * Builds the HMAC headers required by GC-Stats-API's internal-auth
 * middleware (mirrors App\Http\Middleware\InternalServiceAuth::handle()'s
 * own verification exactly: X-Internal-Timestamp + HMAC-SHA256 over
 * "{timestamp}.{method}.{path}.{body}", keyed by the shared
 * services.internal.secret). Factored out of DataExplorerService so every
 * caller of a /internal/* GC-Stats-API route signs requests the same way,
 * instead of re-deriving the scheme per service.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class InternalApiSigner
{
    /**
     * @return array<string, string>
     */
    public static function headers(string $method, string $path, string $body): array
    {
        $timestamp = (string) time();
        $secret = (string) config('services.internal.secret');

        $payload = "{$timestamp}.{$method}.{$path}.{$body}";

        return [
            'X-Internal-Timestamp' => $timestamp,
            'X-Internal-Signature' => hash_hmac('sha256', $payload, $secret),
            'X-Service-Name' => 'gc-stats-website',
        ];
    }
}
