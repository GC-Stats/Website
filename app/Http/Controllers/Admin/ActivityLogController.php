<?php

/**
 * GC-Stats — Admin: activity log viewer
 *
 * Read-only view over spatie/laravel-activitylog's records (log names:
 * account, moderation, administration). Access is one permission per log
 * type (App\Support\AdminPermissions), so the query only ever returns the
 * types the current user actually holds.
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
