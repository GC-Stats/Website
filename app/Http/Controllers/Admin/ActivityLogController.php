<?php

/**
 * GC-Stats — Admin: activity log viewer
 *
 * Read-only view over spatie/laravel-activitylog's Activity records —
 * nothing here writes to the log, it only surfaces what other services
 * already record (see log names 'account' and 'moderation' throughout
 * app/Actions/Fortify, app/Services and AppServiceProvider).
 *
 * Access is split one permission per log type (activity.account,
 * activity.moderation — see App\Support\AdminPermissions), not a single
 * umbrella permission: a role can be granted visibility into moderation
 * actions without also seeing account/login activity. The route itself is
 * gated by the composite 'activity.view' Gate (true if the user holds any
 * one of those); this controller additionally restricts the query to only
 * the specific log types the user actually holds, so "all logs" never
 * means more than "all logs I'm allowed to see" — including when a log
 * filter is requested directly via the query string.
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
    public function index(Request $request): View
    {
        $user = $request->user();

        $allowedLogNames = collect(AdminPermissions::grouped()['activity'])
            ->filter(fn ($permission) => $user->can($permission))
            ->map(fn ($permission) => str($permission)->after('activity.')->toString())
            ->values();

        abort_if($allowedLogNames->isEmpty(), 403);

        $logName = $request->get('log');
        $logName = $allowedLogNames->contains($logName) ? $logName : null;

        $causerId = $request->get('causer');

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->whereIn('log_name', $allowedLogNames)
            ->when($logName, fn ($query) => $query->where('log_name', $logName))
            ->when($causerId, fn ($query) => $query->where('causer_id', $causerId)->where('causer_type', User::class))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', [
            'activities' => $activities,
            'logName' => $logName,
            'logNames' => $allowedLogNames,
        ]);
    }
}
