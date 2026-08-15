<?php

/**
 * GC-Stats — User report controller
 *
 * Any authenticated user can flag another account as suspicious. Listing
 * and resolving reports lives in the admin dashboard, see
 * App\Http\Controllers\Admin\ReportController.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Exceptions\CannotReportUserException;
use App\Http\Controllers\Public\Controller;
use App\Models\User;
use App\Models\UserReport;
use App\Services\UserReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserReportController extends Controller
{
    public function store(Request $request, User $user, UserReportService $reports): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', UserReport::CATEGORIES)],
            'reason' => ['required', 'string', 'max:2000'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        try {
            $reports->submit($request->user(), $user, $validated);
        } catch (CannotReportUserException $e) {
            return back()->withErrors(['report' => $e->getMessage()]);
        }

        return back()->with('status', 'report-submitted');
    }

    /**
     * Lets the reporter (and only the reporter) see the outcome of a report
     * they filed — reached from the "report resolved" notification's link,
     * see UserReportService::resolve(). Strictly scoped: reports carry a
     * reason and the moderator's note, neither meant for the reported
     * party to read.
     */
    public function show(Request $request, UserReport $userReport): View
    {
        abort_unless($userReport->reporter_id === $request->user()->id, 403);

        $userReport->load(['reportedUser:id,name,username', 'reportedMessage.user:id,name,username', 'emote', 'reactable', 'reviewedBy:id,name,username']);

        return view('auth.reports.show', ['report' => $userReport]);
    }
}
