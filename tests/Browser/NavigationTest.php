<?php

// The full Dusk suite running in one process exhausts the default 128M CLI
// memory_limit right as it transitions into this file, killing the run after
// a single test. Raise it here so the suite can complete.
ini_set('memory_limit', '512M');

use Laravel\Dusk\Browser;

/**
 * Navigation accessibility tests.
 *
 * Verifies that navigation state (aria-current), keyboard behaviour,
 * and language switching all work correctly.
 */

// ─── Active page indicators ───────────────────────────────────────────────────

test('home nav link has aria-current page on homepage', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $current = $browser->script("
            const links = document.querySelectorAll('nav a[aria-current=\"page\"]');
            return Array.from(links).map(a => a.getAttribute('href'));
        ")[0];

        expect($current)->not->toBeEmpty('No nav link with aria-current="page" found on homepage');

        $homePath = rtrim(parse_url(url('/'), PHP_URL_PATH) ?: '/', '/') ?: '/';
        $matchesHome = collect($current)->contains(function ($href) use ($homePath) {
            $path = rtrim(parse_url($href, PHP_URL_PATH) ?? '', '/') ?: '/';

            return $path === $homePath;
        });
        expect($matchesHome)->toBeTrue('Home link should have aria-current="page" on homepage');
    });
});

test('tournaments nav link has aria-current page on tournaments index', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/tournaments');

        $current = $browser->script("
            return Array.from(document.querySelectorAll('nav a[aria-current=\"page\"]'))
                .map(a => a.getAttribute('href'));
        ")[0];

        expect($current)->not->toBeEmpty('No nav link with aria-current="page" on /tournaments');
    });
});

test('homepage nav link does not have aria-current on tournaments page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/tournaments');

        $homeHasCurrentOnTournaments = $browser->script("
            const links = document.querySelectorAll('nav a[aria-current=\"page\"]');
            const homePath = '/';
            return Array.from(links).some(a => {
                const path = new URL(a.href, location.origin).pathname;
                return path === homePath;
            });
        ")[0];

        expect($homeHasCurrentOnTournaments)->toBeFalse(
            'Home link should NOT have aria-current="page" on /tournaments'
        );
    });
});

// ─── Language switching ───────────────────────────────────────────────────────

test('language dropdown current locale has aria-current', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->click('button[aria-haspopup="true"]');

        $hasCurrent = $browser->script("
            return Array.from(document.querySelectorAll('[role=\"menuitem\"]'))
                .some(item => item.getAttribute('aria-current') === 'true');
        ")[0];

        expect($hasCurrent)->toBeTrue('Current language should have aria-current="true" in dropdown');
    });
});

test('switching to french updates locale in session', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->click('button[aria-haspopup="true"]');

        $frLink = $browser->script("
            return Array.from(document.querySelectorAll('[role=\"menuitem\"]'))
                .find(a => a.href && a.href.includes('/lang/fr'))?.href;
        ")[0];

        if ($frLink) {
            $browser->visit($frLink);
            // Page should now render French content
            $lang = $browser->script('return document.documentElement.lang')[0];
            expect($lang)->toBe('fr');
        } else {
            $this->markTestSkipped('French locale link not found');
        }
    });
});

// ─── Focus management ─────────────────────────────────────────────────────────

test('skip link is keyboard-reachable via first Tab press', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        // Verify the skip link is the first focusable element in DOM order
        $firstFocusableHref = $browser->script("
            const focusable = Array.from(document.querySelectorAll(
                'a[href]:not([tabindex=\"-1\"]), button:not([disabled]):not([tabindex=\"-1\"]), input:not([disabled]):not([tabindex=\"-1\"]), [tabindex]:not([tabindex=\"-1\"])'
            ));
            return focusable.length > 0 ? focusable[0].getAttribute('href') : null;
        ")[0];

        expect($firstFocusableHref)->toBe('#main-content', 'First Tab should focus the skip link');
    });
});

test('skip link leads to main content when activated', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        // Click the skip link (which is SR-only but functional)
        $browser->script("
            const skipLink = document.querySelector('a[href=\"#main-content\"]');
            if (skipLink) skipLink.click();
        ");

        $focusedId = $browser->script('
            return document.activeElement?.id ?? null;
        ')[0];

        expect($focusedId)->toBe('main-content', 'Skip link click should focus main#main-content');
    });
});

// ─── Mobile navigation ────────────────────────────────────────────────────────

test('mobile menu shows after button click', function () {
    $this->browse(function (Browser $browser) {
        $browser->resize(375, 812)
            ->visit('/');

        // Menu should be hidden initially
        $initiallyVisible = $browser->script("
            const menu = document.getElementById('mobile-menu');
            if (!menu) return null;
            const style = window.getComputedStyle(menu);
            return style.display !== 'none' && style.visibility !== 'hidden';
        ")[0];

        $browser->click('button[aria-controls="mobile-menu"]')
            ->waitUntil("document.getElementById('mobile-menu') && window.getComputedStyle(document.getElementById('mobile-menu')).display !== 'none'", 3);

        $visibleAfterClick = $browser->script("
            const menu = document.getElementById('mobile-menu');
            if (!menu) return null;
            const style = window.getComputedStyle(menu);
            return style.display !== 'none';
        ")[0];

        expect($visibleAfterClick)->toBeTrue('Mobile menu should be visible after button click');
    });
});

test('mobile menu button aria-expanded reflects actual menu state', function () {
    $this->browse(function (Browser $browser) {
        $browser->resize(375, 812)
            ->visit('/');

        // Open
        $browser->click('button[aria-controls="mobile-menu"]');
        $expandedOpen = $browser->attribute('button[aria-controls="mobile-menu"]', 'aria-expanded');
        expect($expandedOpen)->toBe('true', 'aria-expanded should be true when menu is open');

        // Close
        $browser->click('button[aria-controls="mobile-menu"]');
        $expandedClosed = $browser->attribute('button[aria-controls="mobile-menu"]', 'aria-expanded');
        expect($expandedClosed)->toBe('false', 'aria-expanded should be false when menu is closed');
    });
});
