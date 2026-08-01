<?php

use App\Models\User;
use App\Services\DataExplorerQuotaService;

test('individual monthly quota is the platform total split dynamically across authorized users', function () {
    $totalQuota = (int) config('services.data_explorer.quota');
    $quota = app(DataExplorerQuotaService::class);

    expect($quota->individualMonthlyQuota())->toBe($totalQuota);

    User::factory()->count(2)->create(['data_explorer_enabled' => true]);

    expect($quota->individualMonthlyQuota())->toBe(intdiv($totalQuota, 2));

    User::factory()->create(['data_explorer_enabled' => true]);

    expect($quota->individualMonthlyQuota())->toBe(intdiv($totalQuota, 3));
});

test('claiming a request slot uses the platform key while under quota', function () {
    $user = User::factory()->create(['data_explorer_enabled' => true]);
    $quota = app(DataExplorerQuotaService::class);

    $source = $quota->claimRequestSlot($user);

    expect($source)->toBe(DataExplorerQuotaService::SOURCE_PLATFORM);

    $usage = $user->dataExplorerUsages()->first();

    expect($usage->platform_requests_count)->toBe(1)
        ->and($usage->personal_requests_count)->toBe(0);
});
