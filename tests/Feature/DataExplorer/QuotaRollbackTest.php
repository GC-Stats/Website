<?php

use App\Exceptions\DataExplorerUpstreamException;
use App\Models\User;
use App\Services\DataExplorerQuotaService;
use App\Services\DataExplorerService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.gc_stats_api.base_url' => 'https://gc-stats-api.test']);
});

test('a failure before the LLM was ever reached refunds the quota slot', function () {
    // A bare 401 with no {error,message} body — GC-Stats-API's own
    // InternalServiceAuth-style rejection, not one of NlQueryError's codes,
    // so DataExplorerService must treat it as "never reached the LLM".
    Http::fake(['gc-stats-api.test/*' => Http::response(['error' => 'Unauthorized'], 401)]);

    $user = User::factory()->create(['data_explorer_enabled' => true]);
    $quota = app(DataExplorerQuotaService::class);
    $service = app(DataExplorerService::class);

    try {
        $service->execute($user, 'test question');
    } catch (DataExplorerUpstreamException $e) {
        expect($e->llmAttempted)->toBeFalse();
    }

    $usage = $user->dataExplorerUsages()->first();

    expect($usage->platform_requests_count)->toBe(0);
});

test('an LLM pipeline error (e.g. llm_call_failed) keeps the quota slot spent', function () {
    Http::fake(['gc-stats-api.test/*' => Http::response(['error' => 'llm_call_failed', 'message' => 'upstream 401'], 502)]);

    $user = User::factory()->create(['data_explorer_enabled' => true]);
    $service = app(DataExplorerService::class);

    try {
        $service->execute($user, 'test question');
    } catch (DataExplorerUpstreamException $e) {
        expect($e->llmAttempted)->toBeTrue();
    }

    $usage = $user->dataExplorerUsages()->first();

    expect($usage->platform_requests_count)->toBe(1);
});

test('a successful response keeps the quota slot spent', function () {
    Http::fake(['gc-stats-api.test/*' => Http::response([
        'cube_query' => ['measures' => ['matches.count']],
        'result' => [['matches.count' => '1']],
        'provider_used' => 'platform',
    ], 200)]);

    $user = User::factory()->create(['data_explorer_enabled' => true]);
    $service = app(DataExplorerService::class);

    $service->execute($user, 'test question');

    $usage = $user->dataExplorerUsages()->first();

    expect($usage->platform_requests_count)->toBe(1);
});
