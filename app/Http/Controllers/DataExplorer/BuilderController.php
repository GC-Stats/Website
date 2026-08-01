<?php

/**
 * GC-Stats — Data Explorer query builder
 *
 * The direct-to-Cube alternative to the AI query screen: no LLM, no quota —
 * a user picks measures/dimensions/filters themselves and this forwards the
 * query straight to GC-Stats-API's /internal/cube-query. Open to every
 * authenticated user, same as the AI query screen, just without any of its
 * platform-key/BYOK machinery since there's no per-request cost to manage
 * here.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\DataExplorer;

use App\Exceptions\DataExplorerUpstreamException;
use App\Http\Controllers\Public\Controller;
use App\Services\DataExplorerCubeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuilderController extends Controller
{
    public function index(DataExplorerCubeService $cube): View
    {
        return view('data-explorer.builder', [
            'schema' => $cube->schema(),
            'operators' => DataExplorerCubeService::OPERATORS,
        ]);
    }

    public function execute(Request $request, DataExplorerCubeService $cube): JsonResponse
    {
        $validated = $request->validate([
            'measures' => ['sometimes', 'array'],
            'measures.*' => ['string'],
            'dimensions' => ['sometimes', 'array'],
            'dimensions.*' => ['string'],
            'filters' => ['sometimes', 'array'],
            'filters.*.member' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', Rule::in(DataExplorerCubeService::OPERATORS)],
            'filters.*.values' => ['sometimes', 'array'],
            'filters.*.values.*' => ['string'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        if (empty($validated['measures']) && empty($validated['dimensions'])) {
            return response()->json([
                'error' => __('data_explorer.builder.errors.empty_query'),
                'reason' => 'empty_query',
            ], 422);
        }

        try {
            $result = $cube->execute($request->user(), $validated);
        } catch (DataExplorerUpstreamException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'reason' => 'upstream_failed',
                'retry' => $e->retryable,
                'error_id' => $e->requestId,
            ], 502);
        }

        return response()->json($result);
    }
}
