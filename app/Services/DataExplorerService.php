<?php

/**
 * GC-Stats — Data Explorer routing service
 *
 * Laravel never talks to an LLM provider directly and never sends a key to
 * the frontend — it only decides *which key source* a request should use
 * (platform or the user's own BYOK key, via DataExplorerQuotaService) and
 * forwards the prompt to GC-Stats-API's POST /internal/nl-query (that's the
 * Rust API's own route name — unrelated to this feature's Laravel-side
 * naming, do not rename it here), which owns the actual NL -> Cube query ->
 * execution pipeline. The platform's own OpenAI/Anthropic key is
 * provisioned in the Rust API's own config, not here — choosing which
 * LLM/provider the platform uses is explicitly out of scope for Laravel.
 * `llm_provider: 'platform'` tells the Rust API to use its own provisioned
 * key; any other value ('openai'/'anthropic') is a BYOK request and must be
 * paired with `api_key` — confirmed directly against a running
 * GC-Stats-API instance. Requests are signed the same way
 * InternalServiceAuth (app/Http/Middleware/InternalServiceAuth.php)
 * verifies inbound service calls, reusing the existing Laravel<->Rust
 * shared-secret contract rather than inventing a second one.
 *
 * Error handling mirrors GC-Stats-API's own NlQueryError contract
 * (src/nlquery/error.rs in the API repo): a JSON body shaped
 * {"error": "<code>", "message": "..."} with one of llm_call_failed /
 * invalid_cube_query / cube_execution_failed / timeout means the pipeline
 * reached (at least) the LLM call — so the quota slot claimed up front
 * stays spent. Anything else (a connection failure, a 401 from our own
 * signature being rejected, a malformed/non-JSON response) means the LLM
 * was never reached, so that slot is refunded — see
 * DataExplorerQuotaService::releaseRequestSlot().
 *
 * Every failure is also persisted to data_explorer_error_logs (request_id,
 * payload, raw upstream error) and the same request_id is surfaced to the
 * user as a reference code — the message they see stays safe/translated,
 * but the full detail is one lookup away for support/debugging.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Exceptions\DataExplorerQuotaExceededException;
use App\Exceptions\DataExplorerUpstreamException;
use App\Models\DataExplorerErrorLog;
use App\Models\User;
use App\Support\InternalApiSigner;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataExplorerService
{
    /** The Rust API's own route name — fixed by GC-Stats-API, not ours to rename. */
    private const ENDPOINT = '/internal/nl-query';

    /** Tells the Rust API to use its own provisioned key instead of a BYOK one. */
    private const LLM_PROVIDER_PLATFORM = 'platform';

    /**
     * NlQueryError variants (GC-Stats-API, src/nlquery/error.rs) that only
     * ever occur once the pipeline has reached the LLM call.
     */
    private const LLM_ERROR_CODES = ['llm_call_failed', 'invalid_cube_query', 'cube_execution_failed', 'timeout'];

    public function __construct(
        private readonly DataExplorerQuotaService $quota,
    ) {}

    /**
     * @return array<string, mixed> the Rust API's decoded JSON response
     *
     * @throws DataExplorerQuotaExceededException
     * @throws DataExplorerUpstreamException
     */
    public function execute(User $user, string $prompt): array
    {
        $source = $this->quota->claimRequestSlot($user);

        $requestId = (string) Str::uuid();

        $payload = [
            'query' => $prompt,
            'request_id' => $requestId,
            'llm_provider' => self::LLM_PROVIDER_PLATFORM,
        ];

        if ($source === DataExplorerQuotaService::SOURCE_PERSONAL) {
            $personalKey = $user->activeDataExplorerApiKey;

            $payload['llm_provider'] = $personalKey->provider;
            $payload['api_key'] = $personalKey->key_encrypted;
        }

        // Never persist the BYOK key itself — only the shape of the request.
        $loggedPayload = ['query' => $prompt, 'llm_provider' => $payload['llm_provider']];

        $baseUrl = rtrim((string) config('services.gc_stats_api.base_url'), '/');
        $body = json_encode($payload);

        try {
            $response = Http::withHeaders(InternalApiSigner::headers('POST', self::ENDPOINT, $body))
                ->timeout(20)
                ->connectTimeout(5)
                ->withBody($body, 'application/json')
                ->post("{$baseUrl}".self::ENDPOINT);
        } catch (\Throwable $e) {
            // Never reached GC-Stats-API at all — nothing was attempted.
            Log::error('DataExplorerService: connection to GC-Stats-API failed', [
                'url' => "{$baseUrl}".self::ENDPOINT,
                'exception' => $e->getMessage(),
            ]);

            $this->quota->releaseRequestSlot($user, $source);

            $this->logError($requestId, $user, $loggedPayload, null, $e->getMessage(), null);

            throw new DataExplorerUpstreamException(
                __('data_explorer.errors.connection_failed'),
                $e,
                llmAttempted: false,
                requestId: $requestId,
            );
        }

        if (! $response->successful()) {
            $this->throwForErrorResponse($user, $source, $response, $requestId, $loggedPayload);
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            Log::error('DataExplorerService: GC-Stats-API returned a non-JSON/non-array response', [
                'url' => "{$baseUrl}".self::ENDPOINT,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->quota->releaseRequestSlot($user, $source);

            $this->logError($requestId, $user, $loggedPayload, null, 'Non-JSON/non-array response', $response->status());

            throw new DataExplorerUpstreamException(llmAttempted: false, requestId: $requestId);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $loggedPayload
     * @return never
     */
    private function throwForErrorResponse(User $user, string $source, Response $response, string $requestId, array $loggedPayload): void
    {
        $decoded = $response->json();
        $code = is_array($decoded) ? ($decoded['error'] ?? null) : null;
        $rustMessage = is_array($decoded) ? ($decoded['message'] ?? null) : $response->body();

        if ($response->status() === 401) {
            Log::warning('DataExplorerService: GC-Stats-API rejected our internal request signature (401)', [
                'body' => $response->body(),
            ]);
        }

        if (in_array($code, self::LLM_ERROR_CODES, true)) {
            Log::error('DataExplorerService: LLM pipeline error from GC-Stats-API', [
                'code' => $code,
                'message' => $rustMessage,
                'status' => $response->status(),
                'source' => $source,
            ]);

            $this->logError($requestId, $user, $loggedPayload, $code, (string) $rustMessage, $response->status());

            throw new DataExplorerUpstreamException(
                $this->userFacingMessage($code, $source),
                llmAttempted: true,
                retryable: $code !== 'invalid_cube_query',
                requestId: $requestId,
            );
        }

        Log::error('DataExplorerService: unexpected error response from GC-Stats-API', [
            'status' => $response->status(),
            'body' => $response->body(),
            'source' => $source,
        ]);

        $this->quota->releaseRequestSlot($user, $source);

        $this->logError($requestId, $user, $loggedPayload, $code, (string) $rustMessage, $response->status());

        throw new DataExplorerUpstreamException(llmAttempted: false, requestId: $requestId);
    }

    /**
     * User-facing copy per GC-Stats-API error code — deliberately doesn't
     * pass the raw Rust message through, so wording stays consistent/
     * translated and never leaks internal details. llm_call_failed on a
     * BYOK request is almost always a bad/expired/out-of-credit personal
     * key, so that case gets a distinct, actionable hint.
     */
    private function userFacingMessage(?string $code, string $source): string
    {
        return match ($code) {
            'llm_call_failed' => $source === DataExplorerQuotaService::SOURCE_PERSONAL
                ? __('data_explorer.errors.llm_call_failed_personal')
                : __('data_explorer.errors.llm_call_failed_platform'),
            'invalid_cube_query' => __('data_explorer.errors.invalid_cube_query'),
            'cube_execution_failed' => __('data_explorer.errors.cube_execution_failed'),
            'timeout' => __('data_explorer.errors.timeout'),
            default => __('data_explorer.errors.upstream_failed'),
        };
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    private function logError(
        string $requestId,
        User $user,
        array $requestPayload,
        ?string $errorCode,
        string $errorMessage,
        ?int $httpStatus,
    ): void {
        DataExplorerErrorLog::create([
            'request_id' => $requestId,
            'user_id' => $user->id,
            'source' => DataExplorerErrorLog::SOURCE_QUERY,
            'request_payload' => $requestPayload,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'http_status' => $httpStatus,
        ]);
    }
}
