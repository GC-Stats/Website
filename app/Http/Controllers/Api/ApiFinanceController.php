<?php

/**
 * GC-Stats — Finance internal API controller
 *
 * Exposes CRUD endpoints to manage the public finance ledger entries
 * (incomes and expenses) from trusted internal services.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiFinanceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'entries' => FinanceEntry::orderByDesc('entry_date')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'amount_usd' => ['required', 'numeric', 'min:0'],
            'amount_eur' => ['required', 'numeric', 'min:0'],
            'source_url' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $entry = FinanceEntry::create($validated);

        return response()->json([
            'success' => true,
            'entry' => $entry,
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $entry = FinanceEntry::findOrFail($id);

        $validated = $request->validate([
            'entry_date' => ['sometimes', 'date'],
            'type' => ['sometimes', 'in:income,expense'],
            'category' => ['sometimes', 'string', 'max:100'],
            'label' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'amount_usd' => ['sometimes', 'numeric', 'min:0'],
            'amount_eur' => ['sometimes', 'numeric', 'min:0'],
            'source_url' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $entry->update($validated);

        return response()->json([
            'success' => true,
            'entry' => $entry->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        FinanceEntry::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
