<?php

/**
 * GC-Stats — Admin: dashboard entry point
 *
 * `/admin` itself isn't a page — it sends the user to the first section
 * their permissions actually grant, since which admin permissions a role
 * holds can vary (see App\Support\AdminPermissions).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Developers;

use App\Http\Controllers\Public\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $requestCount = $user->apiRequestLogs()
            ->whereHas('apiKey', fn ($q) => $q->where('is_active', true))
            ->whereMonth('api_request_log.created_at', now()->month)
            ->whereYear('api_request_log.created_at', now()->year)
            ->count();
        $stats = $user->apiRequestLogs()
            ->whereHas('apiKey', fn ($q) => $q->where('is_active', true))
            ->whereBetween('api_request_log.created_at', [now()->startOfMonth(), now()])
            ->toBase()
            ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as errors,
                    AVG(duration_ms) as avg_duration
                ')
            ->first();

        $total = $stats->total ?? 0;
        $errors = $stats->errors ?? 0;
        $avgDuration = round($stats->avg_duration ?? 0);

        $errorRate = $total > 0 ? round(($errors / $total) * 100, 2) : 0;

        return view('developers.dashboard', [
            'stats' => [
                'api-keys' => $user->apiKeys->where('is_active', 1)->count(),
                'requests' => $requestCount,
                'avg_response_time' => $avgDuration.' ms',
                'error_rate' => $errorRate.'%',
            ],
        ]);
    }
}
