<?php

/**
 * GC-Stats — NL query personal (BYOK) API key controller
 *
 * Lets an authorized user link/unlink their own OpenAI/Anthropic key from
 * account settings — see NlQueryKeyService for validation and encrypted
 * storage.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Public\Controller;
use App\Models\NlQueryApiKey;
use App\Services\NlQueryKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NlQueryKeyController extends Controller
{
    public function store(Request $request, NlQueryKeyService $nlQueryKeys): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(NlQueryApiKey::PROVIDERS)],
            'key' => ['required', 'string', 'max:500'],
        ]);

        try {
            $nlQueryKeys->link($request->user(), $validated['provider'], $validated['key']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'nl-query-key-linked');
    }

    public function destroy(Request $request, NlQueryKeyService $nlQueryKeys): RedirectResponse
    {
        $nlQueryKeys->unlink($request->user());

        return back()->with('status', 'nl-query-key-removed');
    }
}
