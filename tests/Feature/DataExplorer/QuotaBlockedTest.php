<?php

use App\Exceptions\DataExplorerQuotaExceededException;
use App\Models\DataExplorerUsage;
use App\Models\User;
use App\Services\DataExplorerQuotaService;

test('claiming a request slot throws once quota is exhausted and no personal key is linked', function () {
    $user = User::factory()->create(['data_explorer_enabled' => true]);
    $quota = app(DataExplorerQuotaService::class);

    DataExplorerUsage::create([
        'user_id' => $user->id,
        'year' => now()->year,
        'month' => now()->month,
        'platform_requests_count' => $quota->individualMonthlyQuota(),
    ]);

    expect(fn () => $quota->claimRequestSlot($user))->toThrow(DataExplorerQuotaExceededException::class);
});

test('the query endpoint returns a blocked JSON response instead of a 500 when quota is exhausted', function () {
    $user = User::factory()->create(['data_explorer_enabled' => true]);
    $quota = app(DataExplorerQuotaService::class);

    DataExplorerUsage::create([
        'user_id' => $user->id,
        'year' => now()->year,
        'month' => now()->month,
        'platform_requests_count' => $quota->individualMonthlyQuota(),
    ]);

    $response = $this->actingAs($user)->postJson(route('data-explorer.execute'), ['prompt' => 'Who has the most kills?']);

    $response->assertStatus(402)->assertJson(['reason' => 'quota_exceeded']);
});
