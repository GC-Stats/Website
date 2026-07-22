<?php

/**
 * GC-Stats — Admin: activity log viewer
 *
 * Read-only view over spatie/laravel-activitylog's records (log names:
 * account, moderation, administration, team, player, tournament). Access
 * is one permission per log type (App\Support\AdminPermissions), so the
 * query only ever returns the types the current user actually holds.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    private const SORTABLE = ['when', 'causer', 'description', 'subject'];

    public function index(Request $request): View
    {
        $user = $request->user();

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'when', 'desc');

        $allowedLogNames = collect(AdminPermissions::grouped()['activity'])
            ->filter(fn ($permission) => $user->can($permission))
            ->map(fn ($permission) => str($permission)->after('activity.')->toString())
            ->values();

        abort_if($allowedLogNames->isEmpty(), 403);

        $logName = $request->get('log');
        $logName = $allowedLogNames->contains($logName) ? $logName : null;

        $causerId = $request->get('causer');
        $event = $request->string('event')->toString() ?: null;
        $causerName = $request->string('causer_name')->toString() ?: null;
        $dateFrom = $request->string('date_from')->toString() ?: null;
        $dateTo = $request->string('date_to')->toString() ?: null;

        $events = Activity::query()
            ->whereIn('log_name', $allowedLogNames)
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->whereIn('log_name', $allowedLogNames)
            ->when($logName, fn ($query) => $query->where('log_name', $logName))
            ->when($causerId, fn ($query) => $query->where('causer_id', $causerId)->where('causer_type', User::class))
            ->when($event, fn ($query) => $query->where('event', $event))
            ->when($causerName, fn ($query) => $query->whereHasMorph('causer', [User::class], fn ($q) => $q->where('name', 'like', "%{$causerName}%")))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($sort === 'causer', fn ($query) => $query
                ->select('activity_log.*')
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'activity_log.causer_id')
                        ->where('activity_log.causer_type', '=', User::class);
                })
                ->orderBy('users.name', $direction))
            ->when($sort === 'description', fn ($query) => $query->orderBy('description', $direction))
            ->when($sort === 'subject', fn ($query) => $query->orderBy('subject_type', $direction)->orderBy('subject_id', $direction))
            ->when($sort === 'when', fn ($query) => $query->orderBy('created_at', $direction))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', [
            'activities' => $activities,
            'logName' => $logName,
            'logNames' => $allowedLogNames,
            'events' => $events,
            'event' => $event,
            'causerName' => $causerName,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
}
