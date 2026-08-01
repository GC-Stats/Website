<?php

/**
 * GC-Stats — Pronoun-driven grammatical agreement helper
 *
 * Users, Players and Staff each carry a `pronouns` column (one of the
 * FEMININE/MASCULINE/NEUTRAL codes below) used to pick the correctly
 * gendered text for that person in locales with grammatical gender (e.g.
 * French "joueuse"/"joueur"/"joueur·se") instead of the historically
 * hardcoded feminine default. The column stores a locale-agnostic integer
 * rather than a word from any one language, since every lang/{locale}/*.php
 * file renders its own wording (or none, for locales without grammatical
 * gender) from the same value.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class Pronouns
{
    public const FEMININE = 0;

    public const MASCULINE = 1;

    public const NEUTRAL = 2;

    public const OPTIONS = [self::FEMININE, self::MASCULINE, self::NEUTRAL];

    /**
     * Pick the form matching $pronouns, falling back to feminine — the
     * site's historical default (Game Changers is a women's league) — for
     * unknown/missing values, and to $feminine again for NEUTRAL when no
     * dedicated inclusive wording was given.
     */
    public static function agree(?int $pronouns, string $feminine, string $masculine, ?string $neutral = null): string
    {
        return match ($pronouns) {
            self::MASCULINE => $masculine,
            self::NEUTRAL => $neutral ?? $feminine,
            default => $feminine,
        };
    }

    /**
     * Resolve a lang key whose value is a [self::FEMININE => ..., ...]
     * array to the string matching $pronouns, applying ":placeholder"
     * replacements the same way trans() does. Lang entries that are still a
     * plain string (locales without grammatical gender, or entries not yet
     * split by pronoun) pass straight through to trans().
     */
    public static function trans(string $key, ?int $pronouns, array $replace = []): string
    {
        $forms = trans($key);

        if (! is_array($forms)) {
            return trans($key, $replace);
        }

        $line = ($pronouns !== null ? ($forms[$pronouns] ?? null) : null)
            ?? $forms[self::FEMININE]
            ?? (string) reset($forms);

        foreach ($replace as $name => $value) {
            $line = str_replace(':'.$name, $value, $line);
        }

        return $line;
    }
}
