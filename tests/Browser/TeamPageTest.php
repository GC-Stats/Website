<?php

use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

/**
 * Team page accessibility tests.
 */
test('team logo has alt text with team name', function () {
    $team = Team::factory()->create(['name' => 'BrowserTeamAlpha']);

    $this->browse(function (Browser $browser) use ($team) {
        $browser->visit(route('teams.show', $team->id));

        $alt = $browser->script("
            const img = document.querySelector('img[alt=\"BrowserTeamAlpha\"]');
            return img ? img.getAttribute('alt') : null;
        ")[0];

        expect($alt)->toBe('BrowserTeamAlpha', 'Team logo must have alt matching team name');
    });
});

test('team page sub-navigation has aria-label', function () {
    $team = Team::factory()->create();

    $this->browse(function (Browser $browser) use ($team) {
        $browser->visit(route('teams.show', $team->id));

        $navsWithLabel = $browser->script("
            return Array.from(document.querySelectorAll('nav[aria-label]')).length;
        ")[0];

        expect($navsWithLabel)->toBeGreaterThanOrEqual(2);
    });
});

test('active team nav tab has aria-current page', function () {
    $team = Team::factory()->create();

    $this->browse(function (Browser $browser) use ($team) {
        $browser->visit(route('teams.show', $team->id));

        $present = $browser->script("
            return Array.from(document.querySelectorAll('nav a[aria-current=\"page\"]')).length;
        ")[0];

        expect($present)->toBeGreaterThan(0);
    });
});

test('team country flag has role img', function () {
    $team = Team::factory()->create(['country_code' => 'DE']);

    $this->browse(function (Browser $browser) use ($team) {
        $browser->visit(route('teams.show', $team->id));

        $flag = $browser->script("
            const el = document.querySelector('span[role=\"img\"].fi');
            return el ? el.getAttribute('role') : null;
        ")[0];

        expect($flag)->toBe('img');
    });
});

test('team social links have aria-label', function () {
    $team = Team::factory()->create([
        'socials' => ['twitter' => 'TeamAccount'],
    ]);

    $this->browse(function (Browser $browser) use ($team) {
        $browser->visit(route('teams.show', $team->id));

        $unsafeLinks = $browser->script("
            return Array.from(document.querySelectorAll('a[target=\"_blank\"]'))
                .filter(a => !a.getAttribute('aria-label'))
                .map(a => a.href);
        ")[0];

        expect($unsafeLinks)->toBeEmpty('All external links on the team page must have aria-label');
    });
});
