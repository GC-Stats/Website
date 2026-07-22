<?php

/**
 * GC-Stats — Admin: analytics
 *
 * Read-only page view analytics computed directly from the `page_views`
 * table (see database/migrations/0018_page_views.php,
 * App\Http\Middleware\LogPageView, App\Console\Commands\SyncPageViews).
 * Mirrors the calculations of the old Dashboard's AnalyticsController.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private const REGIONS = ['EURO', 'AMER', 'APAC', 'OTHE'];

    private const SORTABLE = ['page', 'views'];

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'views', 'desc');

        $uniqueDays = max(
            DB::table('page_views')->select(DB::raw('DATE(viewed_at) as date'))->distinct()->get()->count(),
            1
        );

        $totals = DB::table('page_views')
            ->select('region', DB::raw('SUM(count) as total_count'))
            ->groupBy('region')
            ->pluck('total_count', 'region');

        $dailyAverages = collect(self::REGIONS)->mapWithKeys(fn ($region) => [
            $region => round(($totals[$region] ?? 0) / $uniqueDays, 1),
        ]);

        $hourly = $this->hourlyByRegion();

        $topPages = DB::table('page_views')
            ->select('uri', DB::raw('SUM(count) as total_count'))
            ->where('viewed_at', '>=', now()->subDays(30)->startOfDay())
            ->groupBy('uri')
            ->when($sort === 'page', fn ($query) => $query->orderBy('uri', $direction))
            ->when($sort === 'views', fn ($query) => $query->orderBy('total_count', $direction)->orderBy('uri'))
            ->paginate(30)
            ->withQueryString();

        return view('admin.analytics.index', [
            'regions' => self::REGIONS,
            'totals' => $totals,
            'dailyAverages' => $dailyAverages,
            'hourly' => $hourly,
            'topPages' => $topPages,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * @return array{labels: list<string>, regions: array<string, list<int>>}
     */
    private function hourlyByRegion(): array
    {
        $startPeriod = now()->subHours(23)->startOfHour();

        $rows = DB::table('page_views')
            ->select('region', 'viewed_at', DB::raw('SUM(count) as total_count'))
            ->where('viewed_at', '>=', $startPeriod)
            ->groupBy('region', 'viewed_at')
            ->orderBy('viewed_at')
            ->get();

        $hours = collect(range(0, 23))->map(fn ($i) => $startPeriod->copy()->addHours($i));

        $labels = $hours->map(fn (CarbonInterface $hour) => $hour->format('H:i'))->all();

        $regions = collect(self::REGIONS)->mapWithKeys(function ($region) use ($hours, $rows) {
            $byHour = $rows->where('region', $region)->keyBy(fn ($row) => Carbon::parse($row->viewed_at)->format('Y-m-d H:i'));

            return [$region => $hours->map(fn (CarbonInterface $hour) => (int) ($byHour->get($hour->format('Y-m-d H:i'))->total_count ?? 0))->all()];
        })->all();

        return ['labels' => $labels, 'regions' => $regions];
    }
}
