<?php

/**
 * GC-Stats — Admin: activity log viewer
 *
 * Read-only view over spatie/laravel-activitylog's Activity records —
 * nothing here writes to the log, it only surfaces what other services
 * already record (see log names 'account' and 'moderation' throughout
 * app/Actions/Fortify, app/Services and AppServiceProvider).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    private const LOG_NAMES = ['account', 'moderation'];

    public function index(Request $request): View
    {
        $logName = $request->get('log');
        $causerId = $request->get('causer');

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when(in_array($logName, self::LOG_NAMES, true), fn ($query) => $query->where('log_name', $logName))
            ->when($causerId, fn ($query) => $query->where('causer_id', $causerId)->where('causer_type', User::class))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', [
            'activities' => $activities,
            'logName' => $logName,
            'logNames' => self::LOG_NAMES,
        ]);
    }
}
