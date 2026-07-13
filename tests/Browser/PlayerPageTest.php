<?php

use App\Models\Player;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

/**
 * Player page accessibility tests.
 *
 * Verifies semantic structure, ARIA labels and image alt text on the
 * player profile pages (overview, matches, stats, history).
 */

// ─── Player header ────────────────────────────────────────────────────────────

test('player profile photo has alt text with player handle', function () {
    $player = Player::factory()->create(['handle' => 'TestHandleA1']);

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.show', $player->id));

        $alt = $browser->script("
            const img = document.querySelector('img[alt=\"TestHandleA1\"]');
            return img ? img.getAttribute('alt') : null;
        ")[0];

        expect($alt)->toBe('TestHandleA1', 'Player photo alt must match player handle');
    });
});

test('player country flag has role img and aria-label', function () {
    $player = Player::factory()->create(['country_code' => 'FR']);

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.show', $player->id));

        $flag = $browser->script("
            const el = document.querySelector('span[role=\"img\"].fi');
            return el ? {
                role: el.getAttribute('role'),
                label: el.getAttribute('aria-label')
            } : null;
        ")[0];

        expect($flag)->not->toBeNull('Country flag must have role=img');
        expect($flag['role'])->toBe('img');
        expect($flag['label'])->not->toBeEmpty('Country flag must have aria-label');
    });
});

test('player page sub-navigation has aria-label', function () {
    $player = Player::factory()->create();

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.show', $player->id));

        $navLabels = $browser->script("
            return Array.from(document.querySelectorAll('nav[aria-label]'))
                .map(n => n.getAttribute('aria-label'));
        ")[0];

        expect(count($navLabels))->toBeGreaterThanOrEqual(2,
            'At least the main nav and the player sub-nav should have aria-label'
        );
    });
});

test('active player nav tab has aria-current page', function () {
    $player = Player::factory()->create();

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.show', $player->id));

        $currentTabs = $browser->script("
            return Array.from(document.querySelectorAll('nav a[aria-current=\"page\"]'))
                .map(a => a.textContent.trim());
        ")[0];

        expect($currentTabs)->not->toBeEmpty(
            'The active tab on the player page must have aria-current="page"'
        );
    });
});

test('inactive player nav tabs do not have aria-current', function () {
    $player = Player::factory()->create();

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.show', $player->id));

        $allCurrentLinks = $browser->script("
            return Array.from(document.querySelectorAll('nav a[aria-current=\"page\"]')).length;
        ")[0];

        // There may be 1 (main nav home/tournament) + 1 (player tab) = 2 max
        expect($allCurrentLinks)->toBeLessThanOrEqual(2,
            'At most two links should carry aria-current="page" simultaneously'
        );
    });
});

// ─── Social links ─────────────────────────────────────────────────────────────

test('player social links have aria-label', function () {
    $player = Player::factory()->create([
        'socials' => ['twitter' => 'TestUser'],
    ]);

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.show', $player->id));

        $socialLinksWithoutLabel = $browser->script("
            return Array.from(document.querySelectorAll('a[target=\"_blank\"]'))
                .filter(a => !a.getAttribute('aria-label'))
                .map(a => a.href);
        ")[0];

        expect($socialLinksWithoutLabel)->toBeEmpty(
            'All external links must have aria-label'
        );
    });
});

test('player social links have rel noopener noreferrer', function () {
    $player = Player::factory()->create([
        'socials' => ['twitter' => 'TestUser'],
    ]);

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.show', $player->id));

        $unsafeLinks = $browser->script("
            return Array.from(document.querySelectorAll('a[target=\"_blank\"]'))
                .filter(a => {
                    const rel = a.getAttribute('rel') || '';
                    return !rel.includes('noopener');
                })
                .map(a => a.href);
        ")[0];

        expect($unsafeLinks)->toBeEmpty('All target=_blank links must have rel=noopener');
    });
});

// ─── Player stats page ────────────────────────────────────────────────────────

test('player stats date inputs have associated labels', function () {
    $player = Player::factory()->create();

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.stats', [$player->id, str($player->handle)->slug()]));

        $unlabelledInputs = $browser->script("
            return Array.from(document.querySelectorAll('input[type=\"date\"]'))
                .filter(input => {
                    const id = input.getAttribute('id');
                    if (!id) return true;
                    return !document.querySelector('label[for=\"' + id + '\"]');
                })
                .map(i => i.id || 'no-id');
        ")[0];

        expect($unlabelledInputs)->toBeEmpty('All date inputs must have an associated <label>');
    });
});

test('player stats filter form has aria-label', function () {
    $player = Player::factory()->create();

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.stats', [$player->id, str($player->handle)->slug()]));

        $formWithLabel = $browser->script("
            return Array.from(document.querySelectorAll('form[aria-label]')).length;
        ")[0];

        expect($formWithLabel)->toBeGreaterThan(0, 'The stats filter form must have aria-label');
    });
});

test('player stats table has scope on headers', function () {
    $player = Player::factory()->create();

    $this->browse(function (Browser $browser) use ($player) {
        $browser->visit(route('players.stats', [$player->id, str($player->handle)->slug()]));

        $headersWithoutScope = $browser->script("
            return Array.from(document.querySelectorAll('table thead th'))
                .filter(th => !th.getAttribute('scope'))
                .length;
        ")[0];

        expect($headersWithoutScope)->toBe(0, 'All <th> in thead must have scope attribute');
    });
});
