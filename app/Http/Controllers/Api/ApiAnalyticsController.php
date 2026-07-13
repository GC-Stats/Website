<?php

/**
 * GC-Stats — Analytics API controller
 *
 * Exposes page view analytics (region totals, hourly breakdown, top pages)
 * for the Dashboard's Analytics page.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAnalyticsController extends Controller
{
    public function summary(): JsonResponse
    {
        $uniqueDays = DB::table('page_views')->select(DB::raw('DATE(viewed_at) as date'))->distinct()->get()->count();

        $totals = DB::table('page_views')
            ->select('region', DB::raw('SUM(count) as total_count'))
            ->groupBy('region')
            ->get()
            ->pluck('total_count', 'region');

        return response()->json([
            'unique_days' => max($uniqueDays, 1),
            'totals' => $totals,
        ]);
    }

    public function hourly(): JsonResponse
    {
        $startPeriod = now()->subHours(23)->startOfHour();

        $data = DB::table('page_views')
            ->select('region', 'viewed_at', DB::raw('SUM(count) as total_count'))
            ->where('viewed_at', '>=', $startPeriod)
            ->groupBy('region', 'viewed_at')
            ->orderBy('viewed_at', 'asc')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function topPages(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 30), 100);

        $topPages = DB::table('page_views')
            ->select('uri', DB::raw('SUM(count) as total_count'))
            ->where('viewed_at', '>=', now()->subDays(30)->startOfDay())
            ->groupBy('uri')
            ->orderByDesc('total_count')
            ->paginate($perPage);

        return response()->json([
            'data' => $topPages->items(),
            'total' => $topPages->total(),
            'per_page' => $topPages->perPage(),
            'current_page' => $topPages->currentPage(),
        ]);
    }
}
