<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\SearchService;

test('typoVariants generates the documented c/k, i/y and double-letter swaps', function () {
    $variants = app(SearchService::class)->typoVariants('cold');

    expect($variants)->toContain('cold')
        ->toContain('kold');
});

test('typoVariants strips accents before generating variants', function () {
    $variants = app(SearchService::class)->typoVariants('crème');

    expect($variants)->toContain('creme');
});

test('search finds a team by an exact substring match', function () {
    Team::factory()->create(['name' => 'Cloud9', 'short_name' => 'C9']);

    $results = app(SearchService::class)->search('cloud9');

    expect($results['teams'])->toHaveCount(1)
        ->and($results['teams'][0]['name'])->toBe('Cloud9');
});

test('search ranks an exact prefix match above a same-substring match on a longer name', function () {
    Player::factory()->create(['handle' => 'Lacy']);
    Player::factory()->create(['handle' => 'xLacyOfficialx']);

    $results = app(SearchService::class)->search('lacy');

    expect($results['players'])->toHaveCount(2)
        ->and($results['players'][0]['handle'])->toBe('Lacy');
});

test('search only returns active tournaments', function () {
    Tournament::factory()->create(['name' => 'Champions Tour', 'active' => true]);
    Tournament::factory()->create(['name' => 'Champions Legacy', 'active' => false]);

    $results = app(SearchService::class)->search('champions');

    expect($results['tournaments'])->toHaveCount(1)
        ->and($results['tournaments'][0]['name'])->toBe('Champions Tour');
});

test('search respects the per-type result limit', function () {
    Team::factory()->count(10)->create(['name' => 'Alpha Team']);

    $results = app(SearchService::class)->search('alpha', perTypeLimit: 3);

    expect($results['teams'])->toHaveCount(3);
});
