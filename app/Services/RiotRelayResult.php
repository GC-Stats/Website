<?php

/**
 * GC-Stats — RiotRelay call result
 *
 * Value object wrapping a RiotRelayClient call, classifying the response
 * into a translated, user-facing error reason instead of exposing the raw
 * HTTP status/body to controllers. See App\Services\RiotRelayClient.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Illuminate\Http\Client\Response;

class RiotRelayResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly int $status,
        public readonly ?array $json,
        public readonly string $errorKey,
        public readonly array $errorReplace,
        public readonly ?Response $response,
    ) {}

    public static function fromResponse(Response $response): self
    {
        if ($response->successful()) {
            $json = $response->json();

            if (! is_array($json)) {
                return new self(false, $response->status(), null, 'invalid_response', [], $response);
            }

            return new self(true, $response->status(), $json, '', [], $response);
        }

        $status = $response->status();
        $body = $response->json();
        $isRelayOwnAuthError = is_array($body) && ($body['error'] ?? null) === 'unauthorized';

        [$key, $replace] = match (true) {
            $status === 400 => ['invalid_request', []],
            $status === 401 && $isRelayOwnAuthError => ['relay_unauthorized', []],
            $status === 401 || $status === 403 => ['riot_unauthorized', []],
            $status === 404 => ['not_found', []],
            $status === 429 => ['rate_limited', ['seconds' => $response->header('retry-after') ?: '?']],
            $status === 502 => ['relay_unreachable', []],
            $status === 503 => ['cache_unavailable', []],
            $status >= 500 => ['riot_error', ['status' => (string) $status]],
            default => ['unknown', ['status' => (string) $status]],
        };

        return new self(false, $status, is_array($body) ? $body : null, $key, $replace, $response);
    }

    public static function unreachable(): self
    {
        return new self(false, 0, null, 'relay_unreachable', [], null);
    }

    public function message(): string
    {
        return __('admin.matches.maps.errors.'.$this->errorKey, $this->errorReplace);
    }
}
