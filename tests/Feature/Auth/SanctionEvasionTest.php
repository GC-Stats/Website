<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Sanction;
use App\Models\SanctionIdentity;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SanctionService;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteTwoUser;

function fakeSocialiteUser(string $id, ?string $email, array $extraRaw = []): SocialiteTwoUser
{
    return SocialiteTwoUser::fake(array_merge([
        'id' => $id,
        'nickname' => 'evader',
        'name' => 'Evader',
        'email' => $email,
    ], $extraRaw));
}

test('email sanction matching is case-insensitive and ignores plus-addressing', function () {
    $victim = User::factory()->create(['email' => 'victim@example.com']);
    $sanctions = app(SanctionService::class);

    $sanction = Sanction::create([
        'user_id' => $victim->id,
        'type' => Sanction::TYPE_BAN,
        'reason' => 'Evasion test',
        'starts_at' => now()->subDay(),
    ]);
    $sanctions->snapshotIdentities($sanction, $victim);

    expect($sanctions->hasActiveSanctionFor(SanctionIdentity::TYPE_EMAIL, 'Victim@Example.com'))->toBeTrue()
        ->and($sanctions->hasActiveSanctionFor(SanctionIdentity::TYPE_EMAIL, 'victim+alt@example.com'))->toBeTrue()
        ->and($sanctions->hasActiveSanctionFor(SanctionIdentity::TYPE_EMAIL, 'VICTIM+1@EXAMPLE.COM'))->toBeTrue()
        ->and($sanctions->hasActiveSanctionFor(SanctionIdentity::TYPE_EMAIL, 'someoneelse@example.com'))->toBeFalse();
});

test('password registration is blocked when re-registering with a case/alias variant of a banned email', function () {
    $victim = User::factory()->create(['email' => 'victim@example.com']);
    $sanctions = app(SanctionService::class);

    $sanction = Sanction::create([
        'user_id' => $victim->id,
        'type' => Sanction::TYPE_BAN,
        'reason' => 'Evasion test',
        'starts_at' => now()->subDay(),
    ]);
    $sanctions->snapshotIdentities($sanction, $victim);

    $victim->delete();

    expect(fn () => app(CreateNewUser::class)->create([
        'name' => 'New Name',
        'username' => 'newname',
        'email' => 'Victim+evasion@Example.com',
        'password' => 'Correct-Horse-Battery-Staple-1!',
        'password_confirmation' => 'Correct-Horse-Battery-Staple-1!',
    ]))->toThrow(ValidationException::class);

    expect(User::where('username', 'newname')->exists())->toBeFalse();
});

test('registering through a social provider is blocked when the email matches a banned identity', function () {
    $victim = User::factory()->create(['email' => 'victim@example.com']);
    $sanctions = app(SanctionService::class);

    $sanction = Sanction::create([
        'user_id' => $victim->id,
        'type' => Sanction::TYPE_BAN,
        'reason' => 'Evasion test',
        'starts_at' => now()->subDay(),
    ]);
    $sanctions->snapshotIdentities($sanction, $victim);

    // Old account deleted (email freed up), attacker signs in with a brand
    // new, never-seen-before Discord identity bearing the banned email.
    $victim->delete();

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf();
    Socialite::shouldReceive('user')
        ->andReturn(fakeSocialiteUser('brand-new-discord-id', 'victim@example.com', ['verified' => true]));

    $this->get(route('social.callback', 'discord'))->assertForbidden();

    expect(User::where('email', 'victim@example.com')->exists())->toBeFalse();
});

test('a globally sanctioned logged-in user cannot link a new social provider', function () {
    $user = User::factory()->create();

    Sanction::create([
        'user_id' => $user->id,
        'type' => Sanction::TYPE_BAN,
        'reason' => 'Active global ban',
        'starts_at' => now()->subDay(),
    ]);

    Socialite::shouldReceive('driver')
        ->with('twitch')
        ->andReturnSelf();
    Socialite::shouldReceive('user')
        ->andReturn(fakeSocialiteUser('some-twitch-id', 'user@example.com'));

    $this->actingAs($user)
        ->get(route('social.callback', 'twitch'))
        ->assertForbidden();

    expect(SocialAccount::where('user_id', $user->id)->exists())->toBeFalse();
});
