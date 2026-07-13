<?php

/**
 * GC-Stats — API request log controller
 *
 * Exposes API request statistics (global averages or per API key), with an
 * optional "last X days" time window.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiRequestLogController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $query = ApiRequestLog::query();

        $this->applyDaysFilter($query, $request);

        $stats = $query->selectRaw('COUNT(*) as total_requests')
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->selectRaw('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count')
            ->first();

        return response()->json([
            'total_requests' => (int) $stats->total_requests,
            'avg_duration_ms' => $stats->avg_duration_ms !== null ? round((float) $stats->avg_duration_ms, 2) : null,
            'error_count' => (int) $stats->error_count,
        ]);
    }

    public function forKey(int $apiKeyId, Request $request): JsonResponse
    {
        $query = ApiRequestLog::query()->where('api_key_id', $apiKeyId);

        $this->applyDaysFilter($query, $request);

        $stats = $query->selectRaw('COUNT(*) as total_requests')
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->selectRaw('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count')
            ->first();

        return response()->json([
            'api_key_id' => $apiKeyId,
            'total_requests' => (int) $stats->total_requests,
            'avg_duration_ms' => $stats->avg_duration_ms !== null ? round((float) $stats->avg_duration_ms, 2) : null,
            'error_count' => (int) $stats->error_count,
        ]);
    }

    private function applyDaysFilter($query, Request $request): void
    {
        if ($request->filled('days')) {
            $days = max(1, (int) $request->input('days'));
            $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
        }
    }
}
