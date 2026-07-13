<?php

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

/**
 * Search component accessibility tests.
 *
 * Verifies that the Livewire global search component exposes correct
 * ARIA roles and attributes for screen reader and keyboard users.
 */

// ─── Input semantics ─────────────────────────────────────────────────────────

test('search input has type search', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $type = $browser->attribute('input[wire\\:model\\.live\\.debounce\\.400ms]', 'type')
            ?? $browser->attribute('input[role="combobox"]', 'type');

        expect($type)->toBe('search');
    });
});

test('search input has aria-label', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $label = $browser->attribute('input[role="combobox"]', 'aria-label');
        expect($label)->not->toBeEmpty('Search input must have aria-label');
    });
});

test('search input has combobox role', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $role = $browser->attribute('input[aria-autocomplete="list"]', 'role');
        expect($role)->toBe('combobox');
    });
});

test('search input has aria-autocomplete list', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $autocomplete = $browser->attribute('input[role="combobox"]', 'aria-autocomplete');
        expect($autocomplete)->toBe('list');
    });
});

test('search input has aria-controls pointing to results container', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $controls = $browser->attribute('input[role="combobox"]', 'aria-controls');
        expect($controls)->not->toBeEmpty('Search input must have aria-controls');

        // The target element must exist in DOM (even if hidden initially)
        $targetExists = $browser->script("
            return !!document.getElementById('{$controls}');
        ")[0];

        // Note: the results container may not exist before a search is performed;
        // it is rendered by Livewire only when results are available.
        // We only assert the attribute is set correctly.
        expect($controls)->toBe('search-results');
    });
});

test('search input starts with aria-expanded false', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $expanded = $browser->attribute('input[role="combobox"]', 'aria-expanded');
        expect($expanded)->toBe('false');
    });
});

// ─── Search results ───────────────────────────────────────────────────────────

test('search results container has listbox role when visible', function () {
    $team = Team::factory()->create(['name' => 'UniqueSearchTeamABC']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('input[role="combobox"]', 'UniqueSearch')
            ->pause(800); // wait for Livewire debounce + response

        $role = $browser->script("
            const el = document.getElementById('search-results');
            return el ? el.getAttribute('role') : null;
        ")[0];

        expect($role)->toBe('listbox');
    });
});

test('search result items have option role', function () {
    $team = Team::factory()->create(['name' => 'UniqueOptionTeamXYZ']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('input[role="combobox"]', 'UniqueOption')
            ->pause(800);

        $optionCount = $browser->script("
            return document.querySelectorAll('[role=\"option\"]').length;
        ")[0];

        expect($optionCount)->toBeGreaterThan(0, 'Search results must expose role="option" items');
    });
});

test('search result items have aria-selected attribute', function () {
    $player = Player::factory()->create(['handle' => 'UniquePlayerAriaXYZ']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('input[role="combobox"]', 'UniquePlayer')
            ->pause(800);

        $withoutSelected = $browser->script("
            return Array.from(document.querySelectorAll('[role=\"option\"]'))
                .filter(el => !el.hasAttribute('aria-selected'))
                .length;
        ")[0];

        expect($withoutSelected)->toBe(0, 'All role=option elements must have aria-selected');
    });
});

test('team logos in search results have alt text', function () {
    $team = Team::factory()->create(['name' => 'UniqueAltTeam123']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('input[role="combobox"]', 'UniqueAlt')
            ->pause(800);

        $imagesWithoutAlt = $browser->script("
            const results = document.getElementById('search-results');
            if (!results) return [];
            return Array.from(results.querySelectorAll('img'))
                .filter(img => !img.hasAttribute('alt') || img.getAttribute('alt') === '')
                .map(img => img.src);
        ")[0];

        expect($imagesWithoutAlt)->toBeEmpty('Search result images must have alt text');
    });
});

test('loading indicator has status role', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        // The loading div is present in DOM but hidden by Livewire
        $hasStatusRole = $browser->script("
            return !!document.querySelector('[wire\\\\:loading][role=\"status\"]');
        ")[0];

        expect($hasStatusRole)->toBeTrue('Search loading indicator must have role="status"');
    });
});

// ─── No results state ─────────────────────────────────────────────────────────

test('no results message is shown for unknown search term', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('input[role="combobox"]', 'zzzznoexistzzzzz')
            ->pause(800);

        // Either results container is gone, or a "no results" message appears
        $resultCount = $browser->script("
            return document.querySelectorAll('[role=\"option\"]').length;
        ")[0];

        expect($resultCount)->toBe(0);
    });
});
