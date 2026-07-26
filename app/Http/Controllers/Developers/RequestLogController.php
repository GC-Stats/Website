<?php

/**
 * GC-Stats — Developers: request history
 *
 * Read-only, filterable log of every request made against one of the
 * current user's own API keys (App\Models\ApiRequestLog), regardless of
 * whether that key is still active. Always scoped to a single key —
 * selected via the {key} route segment — since keys can have very
 * different traffic profiles; see routes/developers.php.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Developers;

use App\Http\Controllers\Public\Controller;
use App\Models\ApiKey;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RequestLogController extends Controller
{
    private const SORTABLE = ['when', 'endpoint', 'status', 'duration'];

    private const STATUS_CLASSES = ['2xx', '3xx', '4xx', '5xx'];

    public function index(Request $request, ApiKey $key): View
    {
        abort_if($key->user_id !== $request->user()->id, 403);

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'when', 'desc');

        $status = $request->string('status')->toString() ?: null;
        $status = in_array($status, self::STATUS_CLASSES, true) ? $status : null;

        $endpoint = $request->string('endpoint')->toString() ?: null;
        $dateFrom = $request->string('date_from')->toString() ?: null;
        $dateTo = $request->string('date_to')->toString() ?: null;

        $endpoints = $key->requestLogs()
            ->toBase()
            ->select('endpoint')
            ->distinct()
            ->orderBy('endpoint')
            ->pluck('endpoint');

        $logs = $key->requestLogs()
            ->when($endpoint, fn ($query) => $query->where('endpoint', $endpoint))
            ->when($status, fn ($query) => $query->whereBetween('status_code', $this->statusRange($status)))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($sort === 'when', fn ($query) => $query->orderBy('created_at', $direction))
            ->when($sort === 'endpoint', fn ($query) => $query->orderBy('endpoint', $direction))
            ->when($sort === 'status', fn ($query) => $query->orderBy('status_code', $direction))
            ->when($sort === 'duration', fn ($query) => $query->orderBy('duration_ms', $direction))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('developers.requests.index', [
            'logs' => $logs,
            'endpoints' => $endpoints,
            'endpoint' => $endpoint,
            'status' => $status,
            'statuses' => self::STATUS_CLASSES,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'keys' => $request->user()->apiKeys()->orderBy('client_name')->get(),
            'key' => $key,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function statusRange(string $class): array
    {
        $base = ((int) $class[0]) * 100;

        return [$base, $base + 99];
    }
}
