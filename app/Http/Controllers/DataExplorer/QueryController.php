<?php

/**
 * GC-Stats — Data Explorer query screen
 *
 * The AI stats query screen is open to every authenticated user — access to
 * the *platform's own* API key is what's restricted (see
 * DataExplorerQuotaService::claimRequestSlot()). A user who isn't authorized
 * for the platform key, or who's used up their share, still gets through if
 * they've linked their own key from the dedicated settings page.
 * execute() is fetch()-driven (not a classic form POST) so the frontend can
 * show an explicit loading state and offer a retry on timeout instead of a
 * frozen page.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\DataExplorer;

use App\Exceptions\DataExplorerQuotaExceededException;
use App\Exceptions\DataExplorerUpstreamException;
use App\Http\Controllers\Public\Controller;
use App\Services\DataExplorerQuotaService;
use App\Services\DataExplorerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function index(Request $request, DataExplorerQuotaService $quota): View
    {
        return view('data-explorer.index', [
            'usage' => $quota->usageSummary($request->user()),
        ]);
    }

    public function execute(Request $request, DataExplorerService $dataExplorer): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $result = $dataExplorer->execute($request->user(), $validated['prompt']);
        } catch (DataExplorerQuotaExceededException $e) {
            return response()->json(['error' => $e->getMessage(), 'reason' => 'quota_exceeded'], 402);
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
