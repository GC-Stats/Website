<?php

use Laravel\Dusk\Browser;

/**
 * Homepage accessibility (a11y) tests using Laravel Dusk.
 *
 * These tests verify WCAG-aligned markup that was added across the views:
 * - Semantic landmarks (main, nav, footer roles)
 * - Skip navigation link
 * - ARIA attributes on interactive components
 * - Alt text on images
 * - Keyboard/focus management
 */

// ─── Document structure ───────────────────────────────────────────────────────

test('html element has lang attribute', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $lang = $browser->script('return document.documentElement.lang')[0];
        expect($lang)->not->toBeEmpty('html[lang] must be set for screen readers');
    });
});

test('page has skip-to-content link', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertPresent('a[href="#main-content"]');
    });
});

test('skip link targets an existing element on the page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertPresent('#main-content');
    });
});

test('skip link is the first focusable element', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $firstFocusable = $browser->script("
            const focusable = document.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex=\"-1\"])'
            );
            const first = focusable[0];
            return first ? (first.getAttribute('href') || first.id || first.tagName) : null;
        ")[0];

        expect($firstFocusable)->toBe('#main-content', 'Skip link should be the first focusable element');
    });
});

test('main element has correct id for skip link target', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $mainId = $browser->attribute('main', 'id');
        expect($mainId)->toBe('main-content');
    });
});

// ─── Landmarks ────────────────────────────────────────────────────────────────

test('page has exactly one main landmark', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $mainCount = $browser->script("
            return document.querySelectorAll('main, [role=\"main\"]').length;
        ")[0];

        expect($mainCount)->toBe(1, 'A page must have exactly one main landmark');
    });
});

test('navigation has aria-label', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $navsWithoutLabel = $browser->script("
            return Array.from(document.querySelectorAll('nav'))
                .filter(nav => !nav.getAttribute('aria-label') && !nav.getAttribute('aria-labelledby'))
                .length;
        ")[0];

        expect($navsWithoutLabel)->toBe(0, 'Every <nav> must have an aria-label or aria-labelledby');
    });
});

test('footer has contentinfo role', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $role = $browser->attribute('footer', 'role');
        expect($role)->toBe('contentinfo');
    });
});

// ─── Images ───────────────────────────────────────────────────────────────────

test('no meaningful images are missing alt attribute', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $imagesWithoutAlt = $browser->script("
            return Array.from(document.querySelectorAll('img'))
                .filter(img => {
                    // Skip images inside aria-hidden containers
                    let el = img.parentElement;
                    while (el) {
                        if (el.getAttribute('aria-hidden') === 'true') return false;
                        el = el.parentElement;
                    }
                    return !img.hasAttribute('alt');
                })
                .map(img => img.getAttribute('src') || 'unknown');
        ")[0];

        expect($imagesWithoutAlt)->toBeEmpty(
            'Found images without alt: '.implode(', ', $imagesWithoutAlt ?? [])
        );
    });
});

// ─── Language switcher ────────────────────────────────────────────────────────

test('language switcher button has aria-label', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $label = $browser->attribute('button[aria-haspopup="true"]', 'aria-label');
        expect($label)->not->toBeEmpty('Language switcher button must have aria-label');
    });
});

test('language switcher starts with aria-expanded false', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $expanded = $browser->attribute('button[aria-haspopup="true"]', 'aria-expanded');
        expect($expanded)->toBe('false');
    });
});

test('language switcher sets aria-expanded true when opened', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->click('button[aria-haspopup="true"]')
            ->waitUntil("document.querySelector('button[aria-haspopup=\"true\"]').getAttribute('aria-expanded') === 'true'", 3);

        $expanded = $browser->attribute('button[aria-haspopup="true"]', 'aria-expanded');
        expect($expanded)->toBe('true');
    });
});

test('language dropdown has role menu', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->click('button[aria-haspopup="true"]');

        $role = $browser->attribute('[role="menu"]', 'role');
        expect($role)->toBe('menu');
    });
});

test('language menu items have role menuitem', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->click('button[aria-haspopup="true"]');

        $menuItemCount = $browser->script("
            return document.querySelectorAll('[role=\"menuitem\"]').length;
        ")[0];

        expect($menuItemCount)->toBeGreaterThan(0, 'Language menu must have at least one menuitem');
    });
});

// ─── Mobile navigation ────────────────────────────────────────────────────────

test('mobile menu button has aria-controls pointing to existing element', function () {
    $this->browse(function (Browser $browser) {
        $browser->resize(375, 812)
            ->visit('/');

        $controls = $browser->attribute('button[aria-controls]', 'aria-controls');
        expect($controls)->not->toBeEmpty();

        $targetExists = $browser->script("
            return !!document.getElementById('".addslashes($controls ?? '')."');
        ")[0];

        expect($targetExists)->toBeTrue("aria-controls=\"{$controls}\" targets a non-existent element");
    });
});

test('mobile menu button starts with aria-expanded false', function () {
    $this->browse(function (Browser $browser) {
        $browser->resize(375, 812)
            ->visit('/');

        $expanded = $browser->attribute('button[aria-controls="mobile-menu"]', 'aria-expanded');
        expect($expanded)->toBe('false');
    });
});

test('mobile menu button sets aria-expanded true when opened', function () {
    $this->browse(function (Browser $browser) {
        $browser->resize(375, 812)
            ->visit('/')
            ->click('button[aria-controls="mobile-menu"]');

        $expanded = $browser->attribute('button[aria-controls="mobile-menu"]', 'aria-expanded');
        expect($expanded)->toBe('true');
    });
});

// ─── External links ───────────────────────────────────────────────────────────

test('all external links in footer have aria-label', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $missingLabel = $browser->script("
            return Array.from(document.querySelectorAll('footer a[target=\"_blank\"]'))
                .filter(a => !a.getAttribute('aria-label'))
                .map(a => a.href);
        ")[0];

        expect($missingLabel)->toBeEmpty(
            'Footer external links without aria-label: '.implode(', ', $missingLabel ?? [])
        );
    });
});

test('all external links have rel noopener', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $unsafeLinks = $browser->script("
            return Array.from(document.querySelectorAll('a[target=\"_blank\"]'))
                .filter(a => {
                    const rel = a.getAttribute('rel') || '';
                    return !rel.includes('noopener');
                })
                .map(a => a.href);
        ")[0];

        expect($unsafeLinks)->toBeEmpty(
            'External links without rel=noopener: '.implode(', ', $unsafeLinks ?? [])
        );
    });
});

// ─── Decorative content ───────────────────────────────────────────────────────

test('decorative font-awesome icons have aria-hidden', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/');

        $unHiddenIcons = $browser->script("
            return Array.from(document.querySelectorAll(
                'i.fas, i.fa-solid, i.fab, i.far, i.fa-brands, i.fa-regular'
            ))
            .filter(i => {
                if (i.getAttribute('aria-hidden') === 'true') return false;
                // Allow icons that have a meaningful aria-label on the icon itself
                if (i.getAttribute('aria-label')) return false;
                return true;
            })
            .map(i => i.className);
        ")[0];

        expect($unHiddenIcons)->toBeEmpty(
            'Decorative icons without aria-hidden: '.implode(', ', array_slice($unHiddenIcons ?? [], 0, 5))
        );
    });
});
