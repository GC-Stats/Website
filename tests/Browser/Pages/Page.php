<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page as BasePage;

abstract class Page extends BasePage
{
    /**
     * Get the global element shortcuts for the site.
     *
     * @return array<string, string>
     */
    public static function siteElements(): array
    {
        return [
            '@skipLink' => 'a[href="#main-content"]',
            '@mainContent' => '#main-content',
            '@mainNav' => 'nav[aria-label]',
            '@footer' => 'footer[role="contentinfo"]',
            '@langButton' => 'button[aria-haspopup="true"]',
            '@langDropdown' => '[role="menu"]',
            '@mobileMenuBtn' => 'button[aria-controls="mobile-menu"]',
            '@mobileMenu' => '#mobile-menu',
            '@searchInput' => 'input[role="combobox"]',
            '@searchResults' => '#search-results',
        ];
    }
}
