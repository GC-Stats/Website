<?php

use App\Models\Tournament;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

/**
 * Tournament page accessibility tests.
 */

// ─── Tournament header ────────────────────────────────────────────────────────

test('tournament logo has alt text with tournament name', function () {
    $tournament = Tournament::factory()->create(['name' => 'BrowserTournamentBeta']);

    $this->browse(function (Browser $browser) use ($tournament) {
        $browser->visit(route('tournaments.show', $tournament->id));

        $alt = $browser->script("
            const img = document.querySelector('img[alt=\"BrowserTournamentBeta\"]');
            return img ? img.getAttribute('alt') : null;
        ")[0];

        expect($alt)->toBe('BrowserTournamentBeta', 'Tournament logo must have alt matching name');
    });
});

test('tournament page sub-navigation has aria-label', function () {
    $tournament = Tournament::factory()->create();

    $this->browse(function (Browser $browser) use ($tournament) {
        $browser->visit(route('tournaments.show', $tournament->id));

        $navsWithLabel = $browser->script("
            return Array.from(document.querySelectorAll('nav[aria-label]')).length;
        ")[0];

        expect($navsWithLabel)->toBeGreaterThanOrEqual(2);
    });
});

test('active tournament nav tab has aria-current page', function () {
    $tournament = Tournament::factory()->create();

    $this->browse(function (Browser $browser) use ($tournament) {
        $browser->visit(route('tournaments.show', $tournament->id));

        $present = $browser->script("
            return Array.from(document.querySelectorAll('nav a[aria-current=\"page\"]')).length;
        ")[0];

        expect($present)->toBeGreaterThan(0, 'Active tab must have aria-current="page"');
    });
});

// ─── Tournament list filters ──────────────────────────────────────────────────

test('tournament index filter dropdowns have aria-expanded', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit(route('tournaments.index'));

        $filterButtons = $browser->script("
            return Array.from(document.querySelectorAll('button[aria-haspopup=\"listbox\"]'))
                .map(btn => ({
                    label: btn.getAttribute('aria-label') || btn.getAttribute('aria-labelledby'),
                    expanded: btn.getAttribute('aria-expanded')
                }));
        ")[0];

        expect($filterButtons)->not->toBeEmpty('Filter dropdowns must be present');

        foreach ($filterButtons as $btn) {
            expect($btn['expanded'])->toBe('false', 'Filter buttons should start collapsed');
        }
    });
});

test('tournament filter dropdowns expand on click', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit(route('tournaments.index'));

        $firstFilter = 'button[aria-haspopup="listbox"]';
        $browser->click($firstFilter);

        $expanded = $browser->attribute($firstFilter, 'aria-expanded');
        expect($expanded)->toBe('true', 'Filter dropdown must set aria-expanded=true when open');
    });
});

test('tournament list filter dropdowns have listbox role on open', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit(route('tournaments.index'))
            ->click('button[aria-haspopup="listbox"]');

        $listbox = $browser->script("
            return !!document.querySelector('[role=\"listbox\"]');
        ")[0];

        expect($listbox)->toBeTrue('Filter option list must have role="listbox"');
    });
});

// ─── Tournament index images ──────────────────────────────────────────────────

test('tournament logos on index page have alt text', function () {
    Tournament::factory()->count(3)->create();

    $this->browse(function (Browser $browser) {
        $browser->visit(route('tournaments.index'));

        $imagesWithoutAlt = $browser->script("
            return Array.from(document.querySelectorAll('img'))
                .filter(img => {
                    let el = img.parentElement;
                    while (el) {
                        if (el.getAttribute('aria-hidden') === 'true') return false;
                        el = el.parentElement;
                    }
                    return !img.hasAttribute('alt') || img.getAttribute('alt') === '';
                })
                .map(img => img.src);
        ")[0];

        expect($imagesWithoutAlt)->toBeEmpty('All tournament logos must have non-empty alt text');
    });
});
