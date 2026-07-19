<?php

/**
 * GC-Stats — Country list
 *
 * Source of truth for the team country_code picker (resources/views/team/_profile-form.blade.php,
 * shared by the team-owner and admin edit pages). Reads the ISO country
 * list already shipped as a real `dependencies` entry via the flag-icons
 * npm package, rather than duplicating a country list by hand — cached
 * since the file never changes at runtime.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class Countries
{
    /**
     * International/no-fixed-country teams use this synthetic code — not
     * a real ISO entry, so it isn't in country.json. Kept as a literal
     * 'inter' to match the existing fi-un special case in
     * resources/views/team/header.blade.php and other flag renderers.
     */
    public const INTERNATIONAL = 'inter';

    /**
     * @return array<string, string> lowercase ISO alpha-2 code => country name, sorted by name, 'inter' first
     */
    public function list(): array
    {
        return Cache::rememberForever('countries.list', function () {
            $path = file_exists(resource_path('data/countries.json'))
                ? resource_path('data/countries.json')
                : base_path('node_modules/flag-icons/country.json');

            $raw = json_decode(file_get_contents($path), true) ?? [];

            $countries = collect($raw)
                ->filter(fn ($country) => $country['iso'] ?? false)
                ->sortBy('name')
                ->pluck('name', 'code')
                ->all();

            return [self::INTERNATIONAL => 'International'] + $countries;
        });
    }
}
