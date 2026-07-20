<?php

/**
 * GC-Stats — Admin: API keys
 *
 * List/create/edit/toggle/regenerate client API keys. The clear key value
 * is never stored — only a SHA-256 hash (App\Models\ApiKey::hashKey()) — and
 * is shown to the operator exactly once via the single-use reveal link
 * (App\Models\ApiKeyReveal, routes/web.php `api-keys.reveal`).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiKeyReveal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('q');

        $keys = ApiKey::query()
            ->when($search, fn ($query) => $query->where('client_name', 'like', '%'.$this->escapeLike($search).'%'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.api-keys.index', [
            'keys' => $keys,
            'search' => $search ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'min:3', 'max:50'],
            'rate_limit' => ['required', 'integer', 'min:1'],
        ]);

        $revealUrl = DB::transaction(function () use ($validated) {
            $clearKey = $this->generateClearKey();

            $key = ApiKey::create([
                'client_name' => $validated['client_name'],
                'rate_limit' => $validated['rate_limit'],
                'is_active' => true,
                'key_hash' => ApiKey::hashKey($clearKey),
            ]);

            return route('api-keys.reveal', ApiKeyReveal::issue($key, $clearKey)->token);
        });

        activity('administration')->causedBy($request->user())
            ->withProperties(['client_name' => $validated['client_name']])->log('api_key.created');

        return back()->with('status', 'api-key-created')->with('reveal_url', $revealUrl);
    }

    public function update(Request $request, ApiKey $key): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'min:3', 'max:50'],
            'rate_limit' => ['required', 'integer', 'min:1'],
        ]);

        $key->update($validated);

        activity('administration')->causedBy($request->user())
            ->performedOn($key)->log('api_key.updated');

        return back()->with('status', 'api-key-updated');
    }

    public function toggleStatus(Request $request, ApiKey $key): RedirectResponse
    {
        $key->update(['is_active' => ! $key->is_active]);

        activity('administration')->causedBy($request->user())
            ->performedOn($key)->withProperties(['is_active' => $key->is_active])
            ->log('api_key.toggled');

        return back()->with('status', 'api-key-toggled');
    }

    public function regenerate(Request $request, ApiKey $key): RedirectResponse
    {
        $revealUrl = DB::transaction(function () use ($key) {
            $clearKey = $this->generateClearKey();

            // Overwriting the hash invalidates the old key immediately.
            $key->update(['key_hash' => ApiKey::hashKey($clearKey)]);

            return route('api-keys.reveal', ApiKeyReveal::issue($key, $clearKey)->token);
        });

        activity('administration')->causedBy($request->user())
            ->performedOn($key)->log('api_key.regenerated');

        return back()->with('status', 'api-key-regenerated')->with('reveal_url', $revealUrl);
    }

    private function generateClearKey(): string
    {
        return 'GCS_'.Str::random(32);
    }
}
