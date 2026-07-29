<?php

/**
 * GC-Stats — Current visitor theme
 *
 * Resolves/persists the visitor's dark/light theme preference server-side,
 * so theme-scoped logos (see Team/Tournament::getLogoAttribute() and
 * HasLogo::resolveLogoUrl()) can be resolved to a single URL at render time
 * instead of shipping both variants and picking one with CSS/JS.
 *
 * Persisted in the `preferences` JSON column for authenticated users (so it
 * follows them across devices) and in the session otherwise. The client-side
 * toggle (resources/js/app.js GCS.setTheme) still flips `data-theme`
 * immediately for a flash-free UI, and reports the choice here so the next
 * server-rendered page reflects it.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CurrentTheme
{
    public const THEMES = ['dark', 'light'];

    /**
     * resources/js/app.js's GCS_THEMES calls the light theme "white", not
     * "light" — normalize its value to our storage/Logo::theme vocabulary.
     */
    private const CLIENT_ALIASES = ['white' => 'light'];

    public static function get(): string
    {
        $user = Auth::user();

        $stored = $user ? ($user->preferences['theme'] ?? null) : null;
        $stored ??= Session::get('theme');

        return self::normalize($stored) ?? 'dark';
    }

    public static function set(string $theme): void
    {
        $theme = self::normalize($theme) ?? 'dark';

        Session::put('theme', $theme);

        if ($user = Auth::user()) {
            $user->preferences = [...($user->preferences ?? []), 'theme' => $theme];
            $user->save();
        }
    }

    private static function normalize(?string $theme): ?string
    {
        $theme = self::CLIENT_ALIASES[$theme] ?? $theme;

        return in_array($theme, self::THEMES, true) ? $theme : null;
    }
}
