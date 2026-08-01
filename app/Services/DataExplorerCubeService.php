<?php

/**
 * GC-Stats — Data Explorer Cube query builder service
 *
 * The direct-to-Cube path: no LLM, no quota — a user picks measures,
 * dimensions and filters themselves and this forwards that query straight
 * to GC-Stats-API's POST /internal/cube-query, which validates it against
 * the real Cube schema and executes it (src/routes/internal.rs in the API
 * repo). schema() feeds the picker UI from GET /internal/cube-schema,
 * cached locally since the Cube catalogue barely changes and Rust already
 * caches it too — no need to hit the network on every page load.
 *
 * Signing follows the same InternalApiSigner contract as DataExplorerService
 * (POST /internal/nl-query) — same shared secret, same Laravel<->Rust trust
 * boundary, just a different route.
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

use App\Exceptions\DataExplorerUpstreamException;
use App\Models\DataExplorerErrorLog;
use App\Models\User;
use App\Support\InternalApiSigner;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataExplorerCubeService
{
    /**
     * The subset of Cube.dev's filter operators offered by the builder UI —
     * enough to cover equality/comparison/presence checks on any field type
     * without needing per-field type-awareness (Cube itself rejects an
     * operator that doesn't make sense for a given field's type, surfaced
     * as invalid_cube_query).
     */
    public const OPERATORS = [
        'equals', 'notEquals',
        'contains', 'notContains',
        'gt', 'gte', 'lt', 'lte',
        'set', 'notSet',
        'inDateRange',
    ];

    private const SCHEMA_ENDPOINT = '/internal/cube-schema';

    private const QUERY_ENDPOINT = '/internal/cube-query';

    private const SCHEMA_CACHE_KEY = 'data_explorer.cube_schema';

    private const SCHEMA_CACHE_TTL_MINUTES = 15;

    /**
     * NlQueryError variants (GC-Stats-API, src/nlquery/error.rs) this path
     * can actually produce — llm_call_failed is impossible here, there's no
     * LLM in the loop.
     */
    private const ERROR_CODES = ['invalid_cube_query', 'cube_execution_failed', 'timeout'];

    /**
     * @return array{measures: list<string>, dimensions: list<string>}
     */
    public function schema(): array
    {
        return Cache::remember(self::SCHEMA_CACHE_KEY, now()->addMinutes(self::SCHEMA_CACHE_TTL_MINUTES), function () {
            $baseUrl = rtrim((string) config('services.gc_stats_api.base_url'), '/');

            $response = Http::withHeaders(InternalApiSigner::headers('GET', self::SCHEMA_ENDPOINT, ''))
                ->timeout(10)
                ->get("{$baseUrl}".self::SCHEMA_ENDPOINT);

            if (! $response->successful()) {
                Log::error('DataExplorerCubeService: failed to fetch Cube schema', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new DataExplorerUpstreamException(__('data_explorer.errors.upstream_failed'));
            }

            $decoded = $response->json();

            $measures = $decoded['measures'] ?? [];
            $dimensions = $decoded['dimensions'] ?? [];

            sort($measures);
            sort($dimensions);

            return ['measures' => $measures, 'dimensions' => $dimensions];
        });
    }

    /**
     * @param  User  $user  who to attribute a failed request's error log to
     * @param  array{measures?: list<string>, dimensions?: list<string>, filters?: list<array{member: string, operator: string, values: list<string>}>, limit?: int|null}  $query
     * @return array<string, mixed> the Rust API's decoded JSON response ({cube_query, result})
     *
     * @throws DataExplorerUpstreamException
     */
    public function execute(User $user, array $query): array
    {
        $requestId = (string) Str::uuid();

        $payload = [
            'query' => $query,
            'request_id' => $requestId,
        ];

        $baseUrl = rtrim((string) config('services.gc_stats_api.base_url'), '/');
        $body = json_encode($payload);

        try {
            $response = Http::withHeaders(InternalApiSigner::headers('POST', self::QUERY_ENDPOINT, $body))
                ->timeout(20)
                ->connectTimeout(5)
                ->withBody($body, 'application/json')
                ->post("{$baseUrl}".self::QUERY_ENDPOINT);
        } catch (\Throwable $e) {
            Log::error('DataExplorerCubeService: connection to GC-Stats-API failed', [
                'url' => "{$baseUrl}".self::QUERY_ENDPOINT,
                'exception' => $e->getMessage(),
            ]);

            $this->logError($requestId, $user, $query, null, $e->getMessage(), null);

            throw new DataExplorerUpstreamException(
                __('data_explorer.errors.connection_failed'),
                $e,
                requestId: $requestId,
            );
        }

        if (! $response->successful()) {
            $this->throwForErrorResponse($user, $query, $response, $requestId);
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            Log::error('DataExplorerCubeService: GC-Stats-API returned a non-JSON/non-array response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->logError($requestId, $user, $query, null, 'Non-JSON/non-array response', $response->status());

            throw new DataExplorerUpstreamException(requestId: $requestId);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return never
     */
    private function throwForErrorResponse(User $user, array $query, Response $response, string $requestId): void
    {
        $decoded = $response->json();
        $code = is_array($decoded) ? ($decoded['error'] ?? null) : null;
        $rustMessage = is_array($decoded) ? ($decoded['message'] ?? null) : $response->body();

        if ($response->status() === 401) {
            Log::warning('DataExplorerCubeService: GC-Stats-API rejected our internal request signature (401)', [
                'body' => $response->body(),
            ]);
        }

        if (in_array($code, self::ERROR_CODES, true)) {
            Log::error('DataExplorerCubeService: Cube pipeline error from GC-Stats-API', [
                'code' => $code,
                'message' => $rustMessage,
                'status' => $response->status(),
            ]);

            $this->logError($requestId, $user, $query, $code, (string) $rustMessage, $response->status());

            throw new DataExplorerUpstreamException(
                $this->userFacingMessage($code, $rustMessage),
                retryable: $code !== 'invalid_cube_query',
                requestId: $requestId,
            );
        }

        Log::error('DataExplorerCubeService: unexpected error response from GC-Stats-API', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $this->logError($requestId, $user, $query, $code, (string) $rustMessage, $response->status());

        throw new DataExplorerUpstreamException(requestId: $requestId);
    }

    /**
     * invalid_cube_query is the one code here a user can actually act on
     * (they picked an empty or now-stale field, e.g. the schema changed
     * since their page loaded) — surfaced with the real Rust message since
     * it names the offending field, unlike DataExplorerService's LLM-facing
     * codes where the raw message isn't useful to a non-technical reader.
     */
    private function userFacingMessage(?string $code, ?string $rustMessage): string
    {
        return match ($code) {
            'invalid_cube_query' => __('data_explorer.builder.errors.invalid_query', ['reason' => $rustMessage ?? '']),
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
            'source' => DataExplorerErrorLog::SOURCE_BUILDER,
            'request_payload' => $requestPayload,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'http_status' => $httpStatus,
        ]);
    }
}
