<?php

/**
 * GC-Stats — Organization profile validation
 *
 * Shared profile-field validation used by both Admin\OrganizationController
 * and Organization\DashboardController (the organization's own dashboard),
 * so the two surfaces can never validate the same fields differently.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationProfileService
{
    /**
     * @return array<string, mixed>
     */
    public function validate(Request $request, ?Organization $organization): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('organization', 'slug')->ignore($organization?->id)],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'max:50'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'liquipedia_link' => ['nullable', 'url', 'max:255'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! HtmlSanitizer::isSafeUrl($value)) {
                    $fail('The '.$attribute.' field must be a valid link.');
                }
            }],
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['types'] = array_values(array_filter($validated['types'] ?? [], fn ($value) => filled($value)));
        $validated['socials'] = array_filter($validated['socials'] ?? [], fn ($value) => filled($value));

        return $validated;
    }
}
