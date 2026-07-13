<?php

// Pest's getJson() always sends a JSON-encoded body, even for GET requests
// with no data ("[]"), so the signed payload must match that exact body —
// not the empty string a real GET request would send.
function signInternalRequest(string $method, string $path, string $body = '[]', ?int $timestamp = null): array
{
    $timestamp ??= time();
    $secret = config('services.internal.secret');
    $payload = "{$timestamp}.{$method}.{$path}.{$body}";

    return [
        'X-Internal-Timestamp' => (string) $timestamp,
        'X-Internal-Signature' => hash_hmac('sha256', $payload, $secret),
    ];
}

beforeEach(function () {
    config(['services.internal.secret' => 'test-internal-secret']);
});

test('request without signature headers is rejected', function () {
    $this->getJson('/api/internal/stats')->assertUnauthorized();
});

test('request with a valid signature is accepted', function () {
    $headers = signInternalRequest('GET', '/api/internal/stats');

    $this->getJson('/api/internal/stats', $headers)->assertSuccessful();
});

test('request with an invalid signature is rejected', function () {
    $headers = signInternalRequest('GET', '/api/internal/stats');
    $headers['X-Internal-Signature'] = 'not-the-right-signature';

    $this->getJson('/api/internal/stats', $headers)->assertUnauthorized();
});

test('request signed for a different path is rejected', function () {
    $headers = signInternalRequest('GET', '/api/internal/analytics/summary');

    $this->getJson('/api/internal/stats', $headers)->assertUnauthorized();
});

test('request older than the allowed drift is rejected', function () {
    $headers = signInternalRequest('GET', '/api/internal/stats', '', time() - 301);

    $this->getJson('/api/internal/stats', $headers)->assertUnauthorized();
});

test('request is rejected when the internal secret is not configured', function () {
    config(['services.internal.secret' => null]);

    $headers = signInternalRequest('GET', '/api/internal/stats');
    // Re-sign is meaningless without a secret, but headers must still be present
    // to reach the "secret not configured" branch rather than the missing-header one.
    $this->getJson('/api/internal/stats', $headers)->assertUnauthorized();
});
