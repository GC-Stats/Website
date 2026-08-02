<?php

use App\Models\User;
use App\Services\DataExplorerCubeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.gc_stats_api.base_url' => 'https://gc-stats-api.test']);
    Cache::forget('data_explorer.cube_schema');
});

test('the builder page renders with the fetched schema', function () {
    Http::fake(['gc-stats-api.test/internal/cube-schema' => Http::response([
        'measures' => ['kill_stats.kill_count'],
        'dimensions' => ['kill_stats.player_name'],
    ], 200)]);

    $user = User::factory()->create(['data_explorer_enabled' => true]);

    $this->actingAs($user)->get(route('data-explorer.builder'))->assertOk();
});

test('the schema fetch is cached instead of hitting GC-Stats-API on every page load', function () {
    Http::fake(['gc-stats-api.test/internal/cube-schema' => Http::response([
        'measures' => ['kill_stats.kill_count'],
        'dimensions' => [],
    ], 200)]);

    app(DataExplorerCubeService::class)->schema();
    app(DataExplorerCubeService::class)->schema();

    Http::assertSentCount(1);
});

test('an empty query (no measures or dimensions) is rejected before reaching GC-Stats-API', function () {
    $user = User::factory()->create(['data_explorer_enabled' => true]);

    $response = $this->actingAs($user)->postJson(route('data-explorer.builder.execute'), [
        'measures' => [],
        'dimensions' => [],
    ]);

    $response->assertStatus(422)->assertJson(['reason' => 'empty_query']);
});

test('a valid builder query is forwarded and the result returned', function () {
    Http::fake(['gc-stats-api.test/internal/cube-query' => Http::response([
        'cube_query' => ['measures' => ['kill_stats.kill_count'], 'dimensions' => [], 'filters' => [], 'limit' => 100],
        'result' => [['kill_stats.kill_count' => '42']],
    ], 200)]);

    $user = User::factory()->create(['data_explorer_enabled' => true]);

    $response = $this->actingAs($user)->postJson(route('data-explorer.builder.execute'), [
        'measures' => ['kill_stats.kill_count'],
        'limit' => 100,
    ]);

    $response->assertOk();
    expect($response->json('result.0')['kill_stats.kill_count'])->toBe('42');
});

test('an unknown-field error from GC-Stats-API surfaces the real reason to the user', function () {
    Http::fake(['gc-stats-api.test/internal/cube-query' => Http::response([
        'error' => 'invalid_cube_query',
        'message' => 'unknown measure: not_a_real_field',
    ], 422)]);

    $user = User::factory()->create(['data_explorer_enabled' => true]);

    $response = $this->actingAs($user)->postJson(route('data-explorer.builder.execute'), [
        'measures' => ['not_a_real_field'],
    ]);

    $response->assertStatus(502);
    expect($response->json('error'))->toContain('unknown measure: not_a_real_field');
});
