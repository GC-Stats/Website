<?php

/**
 * GC-Stats — User report controller
 *
 * Any authenticated user can flag another account as suspicious. Listing
 * and resolving reports is gated behind the `site.moderate` permission.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Exceptions\CannotReportUserException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserReport;
use App\Services\UserReportService;
use Illuminate\Http\JsonResponse;
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

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('site.moderate'), 403);

        $reports = UserReport::with(['reporter:id,name', 'reportedUser:id,name', 'team:id,name'])
            ->where('status', $request->get('status', UserReport::STATUS_PENDING))
            ->latest()
            ->paginate(25);

        return response()->json($reports);
    }

    public function resolve(Request $request, UserReport $userReport, UserReportService $reports): RedirectResponse
    {
        abort_unless($request->user()->can('site.moderate'), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', [
                UserReport::STATUS_REVIEWING,
                UserReport::STATUS_ACTIONED,
                UserReport::STATUS_DISMISSED,
            ])],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $reports->resolve($userReport, $request->user(), $validated['status'], $validated['resolution_note'] ?? null);

        return back()->with('status', 'report-resolved');
    }
}
