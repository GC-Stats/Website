<?php

use App\Exceptions\DataExplorerQuotaExceededException;
use App\Models\DataExplorerApiKey;
use App\Models\User;
use App\Services\DataExplorerQuotaService;

test('the query screen is reachable by any authenticated user, authorized or not', function () {
    $user = User::factory()->create(['data_explorer_enabled' => false]);

    $this->actingAs($user)->get(route('data-explorer.index'))->assertOk();
});

test('the settings and docs pages render for an unauthorized user too', function () {
    $user = User::factory()->create(['data_explorer_enabled' => false]);

    $this->actingAs($user)->get(route('data-explorer.settings'))->assertOk();
    $this->actingAs($user)->get(route('data-explorer.docs'))->assertOk();
});

test('an unauthorized user has no path to the platform key even if there is unused quota', function () {
    $user = User::factory()->create(['data_explorer_enabled' => false]);
    $quota = app(DataExplorerQuotaService::class);

    expect(fn () => $quota->claimRequestSlot($user))->toThrow(DataExplorerQuotaExceededException::class);
});

test('an unauthorized user with a valid active personal key can still query', function () {
    $user = User::factory()->create(['data_explorer_enabled' => false]);

    DataExplorerApiKey::create([
        'user_id' => $user->id,
        'provider' => DataExplorerApiKey::PROVIDER_ANTHROPIC,
        'is_active' => true,
        'key_encrypted' => 'sk-fake-test-key',
        'linked_at' => now(),
        'last_validated_at' => now(),
        'last_validation_status' => DataExplorerApiKey::VALIDATION_VALID,
    ]);

    $quota = app(DataExplorerQuotaService::class);

    expect($quota->claimRequestSlot($user))->toBe(DataExplorerQuotaService::SOURCE_PERSONAL);
});

test('usage summary reports a distinct reason for "not authorized" vs "quota exceeded"', function () {
    $quota = app(DataExplorerQuotaService::class);

    $unauthorized = User::factory()->create(['data_explorer_enabled' => false]);
    $summary = $quota->usageSummary($unauthorized);

    expect($summary['authorized'])->toBeFalse()
        ->and($summary['quota'])->toBe(0)
        ->and($summary['source'])->toBeNull();
});
