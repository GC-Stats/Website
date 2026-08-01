<?php

use App\Models\DataExplorerApiKey;
use App\Models\User;
use App\Services\DataExplorerKeyService;
use Illuminate\Support\Facades\Http;

test('linking the first key activates it automatically', function () {
    Http::fake(['api.openai.com/*' => Http::response(['data' => []], 200)]);

    $user = User::factory()->create();
    $keys = app(DataExplorerKeyService::class);

    $key = $keys->link($user, DataExplorerApiKey::PROVIDER_OPENAI, 'sk-fake');

    expect($key->is_active)->toBeTrue();
});

test('linking a second provider does not disturb which key is active', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['data' => []], 200),
        'api.anthropic.com/*' => Http::response(['data' => []], 200),
    ]);

    $user = User::factory()->create();
    $keys = app(DataExplorerKeyService::class);

    $keys->link($user, DataExplorerApiKey::PROVIDER_OPENAI, 'sk-fake');
    $keys->link($user, DataExplorerApiKey::PROVIDER_ANTHROPIC, 'sk-ant-fake');

    $openai = DataExplorerApiKey::where('user_id', $user->id)->where('provider', DataExplorerApiKey::PROVIDER_OPENAI)->first();
    $anthropic = DataExplorerApiKey::where('user_id', $user->id)->where('provider', DataExplorerApiKey::PROVIDER_ANTHROPIC)->first();

    expect($openai->is_active)->toBeTrue()
        ->and($anthropic->is_active)->toBeFalse();
});

test('activating a provider deactivates every other one for that user', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['data' => []], 200),
        'api.anthropic.com/*' => Http::response(['data' => []], 200),
    ]);

    $user = User::factory()->create();
    $keys = app(DataExplorerKeyService::class);

    $keys->link($user, DataExplorerApiKey::PROVIDER_OPENAI, 'sk-fake');
    $keys->link($user, DataExplorerApiKey::PROVIDER_ANTHROPIC, 'sk-ant-fake');

    $keys->activate($user, DataExplorerApiKey::PROVIDER_ANTHROPIC);

    $openai = DataExplorerApiKey::where('user_id', $user->id)->where('provider', DataExplorerApiKey::PROVIDER_OPENAI)->first();
    $anthropic = DataExplorerApiKey::where('user_id', $user->id)->where('provider', DataExplorerApiKey::PROVIDER_ANTHROPIC)->first();

    expect($openai->is_active)->toBeFalse()
        ->and($anthropic->is_active)->toBeTrue();

    expect($user->fresh()->activeDataExplorerApiKey->provider)->toBe(DataExplorerApiKey::PROVIDER_ANTHROPIC);
});
