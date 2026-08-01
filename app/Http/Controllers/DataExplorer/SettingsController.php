<?php

/**
 * GC-Stats — Data Explorer settings
 *
 * Dedicated config page for the Data Explorer feature: link/unlink a
 * personal (BYOK) OpenAI and/or Anthropic key, pick which one is active,
 * and see which model each provider runs on. Open to every authenticated
 * user — this is exactly how someone without platform-key access (or over
 * their monthly share) gets in, see DataExplorerQuotaService.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\DataExplorer;

use App\Http\Controllers\Public\Controller;
use App\Models\DataExplorerApiKey;
use App\Services\DataExplorerKeyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('data-explorer.settings', [
            'dataExplorerApiKeys' => $request->user()->dataExplorerApiKeys()->get()->keyBy('provider'),
        ]);
    }

    public function store(Request $request, DataExplorerKeyService $dataExplorerKeys): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(DataExplorerApiKey::PROVIDERS)],
            'key' => ['required', 'string', 'max:500'],
        ]);

        try {
            $dataExplorerKeys->link($request->user(), $validated['provider'], $validated['key']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'data-explorer-key-linked');
    }

    public function activate(Request $request, DataExplorerKeyService $dataExplorerKeys): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(DataExplorerApiKey::PROVIDERS)],
        ]);

        try {
            $dataExplorerKeys->activate($request->user(), $validated['provider']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'data-explorer-key-activated');
    }

    public function destroy(Request $request, DataExplorerKeyService $dataExplorerKeys, string $provider): RedirectResponse
    {
        if (! in_array($provider, DataExplorerApiKey::PROVIDERS, true)) {
            abort(404);
        }

        $dataExplorerKeys->unlink($request->user(), $provider);

        return back()->with('status', 'data-explorer-key-removed');
    }
}
