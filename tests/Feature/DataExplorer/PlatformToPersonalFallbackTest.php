<?php

use App\Models\DataExplorerApiKey;
use App\Models\DataExplorerUsage;
use App\Models\User;
use App\Services\DataExplorerQuotaService;

test('a request falls back to the personal key once the platform quota is used up', function () {
    $user = User::factory()->create(['data_explorer_enabled' => true]);
    $quota = app(DataExplorerQuotaService::class);

    // Sole authorized user => individual quota == the full platform total.
    // Pre-fill this month's usage to exactly that quota so the next claim
    // has to fall through to the personal key.
    DataExplorerUsage::create([
        'user_id' => $user->id,
        'year' => now()->year,
        'month' => now()->month,
        'platform_requests_count' => $quota->individualMonthlyQuota(),
    ]);

    DataExplorerApiKey::create([
        'user_id' => $user->id,
        'provider' => DataExplorerApiKey::PROVIDER_OPENAI,
        'is_active' => true,
        'key_encrypted' => 'sk-fake-test-key',
        'linked_at' => now(),
        'last_validated_at' => now(),
        'last_validation_status' => DataExplorerApiKey::VALIDATION_VALID,
    ]);

    $source = $quota->claimRequestSlot($user);

    expect($source)->toBe(DataExplorerQuotaService::SOURCE_PERSONAL);

    $usage = $user->dataExplorerUsages()->first();

    expect($usage->personal_requests_count)->toBe(1)
        ->and($usage->platform_requests_count)->toBe($quota->individualMonthlyQuota());
});
