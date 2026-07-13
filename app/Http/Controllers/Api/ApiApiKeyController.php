<?php

/**
 * GC-Stats — API key management controller
 *
 * Exposes CRUD endpoints for the Dashboard's API Keys page (list, create,
 * toggle status).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiKeyReveal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ApiKey::query();

        if ($request->filled('q')) {
            $query->where('client_name', 'like', '%'.$request->input('q').'%');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json(
            $query->latest()
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => 'required|string|min:3|max:50',
        ]);

        [$key, $revealUrl] = DB::transaction(function () use ($validated) {
            $clearKey = $this->generateClearKey();

            $key = ApiKey::create([
                'client_name' => $validated['client_name'],
                'is_active' => true,
                'key_hash' => ApiKey::hashKey($clearKey),
            ]);

            return [$key, route('api-keys.reveal', ApiKeyReveal::issue($key, $clearKey)->token)];
        });

        return response()->json([
            'success' => true,
            'key' => $key,
            // Single-use link: the Dashboard sends the user here to read the
            // clear key once. The key itself never leaves this response.
            'reveal_url' => $revealUrl,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $key = ApiKey::findOrFail($id);

        $validated = $request->validate([
            'client_name' => 'sometimes|required|string|min:3|max:50',
            'rate_limit' => 'sometimes|required|integer|min:1',
        ]);

        $key->update($validated);

        return response()->json([
            'success' => true,
            'key' => $key->fresh(),
        ]);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $key = ApiKey::findOrFail($id);
        $key->update(['is_active' => ! $key->is_active]);

        return response()->json([
            'success' => true,
            'key' => $key->fresh(),
        ]);
    }

    public function regenerate(int $id): JsonResponse
    {
        $key = ApiKey::findOrFail($id);

        $revealUrl = DB::transaction(function () use ($key) {
            $clearKey = $this->generateClearKey();

            // Overwriting the hash invalidates the old key immediately.
            $key->update(['key_hash' => ApiKey::hashKey($clearKey)]);

            return route('api-keys.reveal', ApiKeyReveal::issue($key, $clearKey)->token);
        });

        return response()->json([
            'success' => true,
            'key' => $key->fresh(),
            'reveal_url' => $revealUrl,
        ]);
    }

    private function generateClearKey(): string
    {
        return 'GCS_'.Str::random(32);
    }
}
