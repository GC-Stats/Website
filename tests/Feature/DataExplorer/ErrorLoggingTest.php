<?php

use App\Models\DataExplorerErrorLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['services.gc_stats_api.base_url' => 'https://gc-stats-api.test']);
});

test('a builder upstream failure is logged with the same request_id returned to the user', function () {
    Http::fake(['gc-stats-api.test/internal/cube-query' => Http::response([
        'error' => 'cube_execution_failed', 'message' => 'boom',
    ], 502)]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('data-explorer.builder.execute'), [
        'measures' => ['matches.count'],
    ]);

    $response->assertStatus(502);
    $errorId = $response->json('error_id');

    expect($errorId)->not->toBeNull();

    $log = DataExplorerErrorLog::where('request_id', $errorId)->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->source)->toBe(DataExplorerErrorLog::SOURCE_BUILDER)
        ->and($log->error_code)->toBe('cube_execution_failed')
        ->and($log->http_status)->toBe(502);
});

test('an AI query upstream failure is logged and does not leak the BYOK key into the payload', function () {
    Http::fake(['gc-stats-api.test/internal/nl-query' => Http::response([
        'error' => 'llm_call_failed', 'message' => 'upstream boom',
    ], 502)]);

    $user = User::factory()->create(['data_explorer_enabled' => true]);

    $response = $this->actingAs($user)->postJson(route('data-explorer.execute'), [
        'prompt' => 'who has the most kills',
    ]);

    $response->assertStatus(502);
    $errorId = $response->json('error_id');

    $log = DataExplorerErrorLog::where('request_id', $errorId)->first();

    expect($log)->not->toBeNull()
        ->and($log->source)->toBe(DataExplorerErrorLog::SOURCE_QUERY)
        ->and($log->request_payload)->toHaveKey('query')
        ->and($log->request_payload)->not->toHaveKey('api_key');
});

test('the pruning command deletes error logs older than 30 days but keeps recent ones', function () {
    $user = User::factory()->create();

    $old = DataExplorerErrorLog::create([
        'request_id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'source' => DataExplorerErrorLog::SOURCE_QUERY,
        'request_payload' => ['query' => 'old'],
        'error_message' => 'old failure',
    ]);
    $old->forceFill(['created_at' => now()->subDays(31)])->save();

    $recent = DataExplorerErrorLog::create([
        'request_id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'source' => DataExplorerErrorLog::SOURCE_QUERY,
        'request_payload' => ['query' => 'recent'],
        'error_message' => 'recent failure',
    ]);

    $this->artisan('app:prune-data-explorer-error-logs')->assertSuccessful();

    expect(DataExplorerErrorLog::find($old->id))->toBeNull()
        ->and(DataExplorerErrorLog::find($recent->id))->not->toBeNull();
});
