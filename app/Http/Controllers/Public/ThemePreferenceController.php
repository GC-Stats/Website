<?php

/**
 * GC-Stats — Theme preference endpoint
 *
 * Persists the visitor's dark/light theme choice server-side (see
 * App\Support\CurrentTheme) so theme-scoped team/tournament logos can be
 * resolved to a single URL on the next server render. Called by the
 * client-side theme toggle (resources/js/app.js GCS.setTheme) right after it
 * flips `data-theme` for the immediate, flash-free UI update.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Support\CurrentTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemePreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        // Accepts both our storage vocabulary ('dark'/'light') and the
        // client's own ('dark'/'white' — see resources/js/app.js GCS_THEMES);
        // CurrentTheme::set() normalizes either to 'dark'/'light'.
        $validated = $request->validate([
            'theme' => ['required', 'in:dark,light,white'],
        ]);

        CurrentTheme::set($validated['theme']);

        return response()->json(['success' => true]);
    }
}
