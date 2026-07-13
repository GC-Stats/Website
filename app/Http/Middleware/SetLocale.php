<?php

/**
 * GC-Stats — Locale middleware
 *
 * Applies the user's locale (stored in session) to the application if it
 * is one of the supported locales, falling back silently otherwise.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (session()->has('locale')) {
                $locale = session()->get('locale');
                if (array_key_exists($locale, config('locales.supported'))) {
                    App::setLocale($locale);
                }
            }
        } catch (\Exception $e) {
            // Session unavailable, skip locale
        }

        return $next($request);
    }
}
