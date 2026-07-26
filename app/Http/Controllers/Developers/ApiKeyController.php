<?php

/**
 * GC-Stats — Developers: API keys
 *
 * List/edit/toggle/regenerate client API keys. The clear key value
 * is never stored — only a SHA-256 hash (App\Models\ApiKey::hashKey()) — and
 * is shown to the operator exactly once via the single-use reveal link
 * (App\Models\ApiKeyReveal, routes/web.php `api-keys.reveal`).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Developers;

use App\Http\Controllers\Public\Controller;
use App\Models\ApiKey;
use App\Models\ApiKeyReveal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    private const SORTABLE = ['client_name', 'rate_limit', 'status'];

    public function index(Request $request): View
    {
        $user = auth()->user();
        $search = $request->get('q');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'created_at', 'asc');

        $keys = $user->apiKeys()
            ->when($search, fn ($query) => $query->where('client_name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($sort === 'client_name', fn ($query) => $query->orderBy('client_name', $direction))
            ->when($sort === 'rate_limit', fn ($query) => $query->orderBy('rate_limit', $direction))
            ->when($sort === 'status', fn ($query) => $query->orderBy('is_active', $direction))
            ->when($sort === 'created_at', fn ($query) => $query->orderBy('created_at', $direction))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('developers.api-keys.index', [
            'keys' => $keys,
            'search' => $search ?? '',
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function regenerate(Request $request, ApiKey $key): RedirectResponse
    {
        if ($key->user_id !== $request->user()->id) {
            abort(403, __('developers.dashboard.api_keys.403'));
        }

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
