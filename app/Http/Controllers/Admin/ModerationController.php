<?php

/**
 * GC-Stats — Admin: moderation suspects queue
 *
 * System-flagged forum content (see App\Services\OpenAiModerationService /
 * App\Models\ModerationSuspect) — distinct from the user-submitted queue in
 * ReportController. Gated behind `moderation.view`/`moderation.resolve`.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\ModerationSuspect;
use App\Models\Sanction;
use App\Services\SanctionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', ModerationSuspect::STATUS_PENDING);

        $suspects = ModerationSuspect::with(['user:id,name,username', 'thread', 'subject'])
            ->when(in_array($status, ModerationSuspect::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.moderation.index', [
            'suspects' => $suspects,
            'status' => $status,
            'statuses' => ModerationSuspect::STATUSES,
        ]);
    }

    /**
     * Two outcomes: 'dismissed' means the flag was a false positive — the
     * message is unhidden (approved) — 'actioned' means it was correct and
     * the message stays hidden. Sanctioning the poster is a separate
     * action (the sanction modal in the view), not tied to this endpoint.
     */
    public function resolve(Request $request, ModerationSuspect $moderationSuspect): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.ModerationSuspect::STATUS_DISMISSED.','.ModerationSuspect::STATUS_ACTIONED],
        ]);

        $moderationSuspect->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($validated['status'] === ModerationSuspect::STATUS_DISMISSED && $moderationSuspect->subject?->isFillable('hidden_at')) {
            $moderationSuspect->subject->update(['hidden_at' => null]);
        }

        activity('moderation')
            ->performedOn($moderationSuspect)
            ->causedBy($request->user())
            ->withProperties(['status' => $validated['status']])
            ->log('moderation.suspect_resolved');

        return redirect()->route('admin.moderation.index', ['status' => $request->get('status', ModerationSuspect::STATUS_PENDING)])
            ->with('status', 'suspect-resolved');
    }

    /**
     * Lifts the automod mute (see App\Services\SanctionService::
     * issueSystemMute(), App\Jobs\ModerateForumMessage) tied to this
     * suspect's user. Gated by `moderation.resolve` — the same permission
     * that already resolves suspects in this queue — rather than
     * `sanctions.revoke`, so any staff member who can work the automod
     * queue can lift the mute it produced, without needing sanction-admin
     * rights. Only ever touches a system-issued mute (issued_by === null);
     * a staff-issued one must go through the normal sanctions UI.
     *
     * Looked up directly by issued_by === null rather than via
     * activeGlobalMuteSanction() — that helper returns whichever mute is
     * currently latest-by-starts_at, which can be a staff-issued one
     * stacked on top of this automod mute (SanctionService::issue() has no
     * stacking guard, unlike issueSystemMute()). Going straight to the
     * system-issued row means this always finds and lifts it, regardless of
     * what else is active.
     */
    public function liftMute(Request $request, ModerationSuspect $moderationSuspect, SanctionService $sanctions): RedirectResponse
    {
        $mute = $moderationSuspect->user
            ?->sanctions()
            ->active()
            ->whereNull('team_id')
            ->where('type', Sanction::TYPE_MUTE)
            ->whereNull('issued_by')
            ->latest('starts_at')
            ->first();

        if ($mute) {
            $sanctions->revoke($mute, $request->user());
        }

        return redirect()->route('admin.moderation.index', ['status' => $request->get('status', ModerationSuspect::STATUS_PENDING)])
            ->with('status', 'mute-lifted');
    }
}
