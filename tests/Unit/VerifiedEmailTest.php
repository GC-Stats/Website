<?php

use App\Support\Socialite\VerifiedEmail;

test('discord email is dropped when the provider marks it unverified', function () {
    expect(VerifiedEmail::resolve('discord', ['verified' => false], 'victim@example.com'))->toBeNull();
});

test('discord email is kept when the provider marks it verified', function () {
    expect(VerifiedEmail::resolve('discord', ['verified' => true], 'victim@example.com'))->toBe('victim@example.com');
});

test('discord email is kept when the raw payload has no verified flag at all', function () {
    expect(VerifiedEmail::resolve('discord', [], 'victim@example.com'))->toBe('victim@example.com');
});

test('twitch and twitter emails are trusted as-is since neither exposes a verified flag', function () {
    expect(VerifiedEmail::resolve('twitch', ['verified' => false], 'victim@example.com'))->toBe('victim@example.com')
        ->and(VerifiedEmail::resolve('twitter', ['verified' => false], 'victim@example.com'))->toBe('victim@example.com');
});

test('a null email stays null regardless of provider or raw payload', function () {
    expect(VerifiedEmail::resolve('discord', ['verified' => true], null))->toBeNull();
});
