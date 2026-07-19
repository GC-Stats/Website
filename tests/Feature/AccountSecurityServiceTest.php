<?php

use App\Exceptions\SocialAccountAlreadyLinkedException;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AccountSecurityService;

test('linking a provider identity already linked to a different user throws', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    SocialAccount::factory()->create([
        'user_id' => $owner->id,
        'provider' => 'discord',
        'provider_id' => '123456789',
    ]);

    app(AccountSecurityService::class)->linkProvider($otherUser, 'discord', [
        'id' => '123456789',
        'nickname' => 'someone',
        'avatar' => null,
        'token' => 'token',
        'refreshToken' => null,
        'expiresIn' => null,
        'createdAt' => null,
    ]);
})->throws(SocialAccountAlreadyLinkedException::class);
