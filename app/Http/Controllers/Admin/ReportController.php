<?php

/**
 * GC-Stats — Admin: user reports
 *
 * Moderation queue for reports submitted via UserReportController::store().
 * Gated behind `reports.view`/`reports.resolve` at the route level
 * (routes/admin.php).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserReport;
use App\Services\UserReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const STATUSES = [
        UserReport::STATUS_PENDING,
        UserReport::STATUS_REVIEWING,
        UserReport::STATUS_ACTIONED,
        UserReport::STATUS_DISMISSED,
    ];

    public function index(Request $request): View
    {
        $status = $request->get('status', UserReport::STATUS_PENDING);

        $reports = UserReport::with(['reporter:id,name,username', 'reportedUser:id,name,username', 'team:id,name'])
            ->when(in_array($status, self::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $status,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(UserReport $userReport): View
    {
        $userReport->load(['reporter:id,name,username,email', 'reportedUser.socialAccounts', 'reportedUser.sanctions', 'team:id,name', 'reviewedBy:id,name,username']);

        return view('admin.reports.show', [
            'report' => $userReport,
            'statuses' => [UserReport::STATUS_REVIEWING, UserReport::STATUS_ACTIONED, UserReport::STATUS_DISMISSED],
        ]);
    }

    public function resolve(Request $request, UserReport $userReport, UserReportService $reports): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', [
                UserReport::STATUS_REVIEWING,
                UserReport::STATUS_ACTIONED,
                UserReport::STATUS_DISMISSED,
            ])],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $reports->resolve($userReport, $request->user(), $validated['status'], $validated['resolution_note'] ?? null);

        return redirect()->route('admin.reports.index')->with('status', 'report-resolved');
    }
}
