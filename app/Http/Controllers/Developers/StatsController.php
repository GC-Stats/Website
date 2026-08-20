<?php

/**
 * GC-Stats — Developers: API usage statistics
 *
 * Read-only stats computed from one of the current user's own API keys
 * (App\Models\ApiKey::requestLogs) — selected via the {key} route
 * segment, since keys can have very different traffic profiles: request
 * volume over a few trailing windows, response-time distribution
 * (min/max/p50/p95/p99), a 30-day daily request/error chart, and the
 * most-used endpoints.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Developers;

use App\Http\Controllers\Public\Controller;
use App\Models\ApiKey;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    private const SORTABLE = ['endpoint', 'requests', 'avg_duration', 'error_rate'];

    public function index(Request $request, ApiKey $key): View
    {
        abort_if($key->user_id !== $request->user()->id, 403, __('developers.dashboard.errors.not_own_key'));

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'requests', 'desc');

        $since = now()->subDays(29)->startOfDay();

        $summary = $key->requestLogs()
            ->where('created_at', '>=', $since)
            ->toBase()
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as errors
            ')
            ->first();

        $total = $summary->total ?? 0;
        $errors = $summary->errors ?? 0;
        $errorRate = $total > 0 ? round(($errors / $total) * 100, 2) : 0;

        $volume = [
            '24h' => $key->requestLogs()->where('created_at', '>=', now()->subDay())->count(),
            '7d' => $key->requestLogs()->where('created_at', '>=', now()->subDays(7))->count(),
            '30d' => $key->requestLogs()->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $latency = $this->latencyStats($key, $since);

        $daily = $this->dailyCounts($key, $since);

        $endpoints = $key->requestLogs()
            ->where('created_at', '>=', $since)
            ->toBase()
            ->select('endpoint')
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('AVG(duration_ms) as avg_duration')
            ->selectRaw('ROUND(COUNT(CASE WHEN status_code >= 400 THEN 1 END) / COUNT(*) * 100, 1) as error_rate')
            ->groupBy('endpoint')
            ->when($sort === 'endpoint', fn ($query) => $query->orderBy('endpoint', $direction))
            ->when($sort === 'requests', fn ($query) => $query->orderBy('requests', $direction))
            ->when($sort === 'avg_duration', fn ($query) => $query->orderBy('avg_duration', $direction))
            ->when($sort === 'error_rate', fn ($query) => $query->orderBy('error_rate', $direction))
            ->paginate(15)
            ->withQueryString();

        return view('developers.stats.index', [
            'total' => $total,
            'errorRate' => $errorRate,
            'volume' => $volume,
            'latency' => $latency,
            'daily' => $daily,
            'endpoints' => $endpoints,
            'keys' => $request->user()->apiKeys()->orderBy('client_name')->get(),
            'key' => $key,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * @return array{min: int, max: int, p50: int, p95: int, p99: int}
     */
    private function latencyStats(ApiKey $key, CarbonInterface $since): array
    {
        $durations = $key->requestLogs()
            ->where('created_at', '>=', $since)
            ->toBase()
            ->orderBy('duration_ms')
            ->pluck('duration_ms')
            ->all();

        if ($durations === []) {
            return ['min' => 0, 'max' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0];
        }

        return [
            'min' => (int) $durations[0],
            'max' => (int) $durations[count($durations) - 1],
            'p50' => $this->percentile($durations, 50),
            'p95' => $this->percentile($durations, 95),
            'p99' => $this->percentile($durations, 99),
        ];
    }

    /**
     * Nearest-rank percentile over an already-sorted (ascending) sample.
     *
     * @param  list<int>  $sorted
     */
    private function percentile(array $sorted, float $percentile): int
    {
        $index = (int) ceil($percentile / 100 * count($sorted)) - 1;
        $index = max(0, min($index, count($sorted) - 1));

        return (int) $sorted[$index];
    }

    /**
     * @return array{labels: list<string>, requests: list<int>, errors: list<int>}
     */
    private function dailyCounts(ApiKey $key, CarbonInterface $since): array
    {
        $rows = $key->requestLogs()
            ->where('created_at', '>=', $since)
            ->toBase()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(CASE WHEN status_code >= 400 THEN 1 END) as errors')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'));

        $days = collect(range(0, 29))->map(fn ($i) => $since->copy()->addDays($i));

        return [
            'labels' => $days->map(fn (CarbonInterface $day) => $day->format('M j'))->all(),
            'requests' => $days->map(fn (CarbonInterface $day) => (int) ($rows->get($day->format('Y-m-d'))->total ?? 0))->all(),
            'errors' => $days->map(fn (CarbonInterface $day) => (int) ($rows->get($day->format('Y-m-d'))->errors ?? 0))->all(),
        ];
    }
}
