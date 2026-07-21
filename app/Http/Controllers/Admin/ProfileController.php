<?php

/**
 * GC-Stats — Admin: profile & preferences
 *
 * A second, admin-scoped edit surface for the signed-in user — basic
 * profile fields (posted to Fortify's existing user-profile-information
 * route, unchanged) plus the site-wide display preferences (theme, accent,
 * language, timezone, time format) that otherwise only live in the public
 * site's nav gear-icon panel. Preferences are stored client-side (see
 * resources/js/app.js's GCS.* helpers) — this page has no dedicated update
 * route for them.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }
}
