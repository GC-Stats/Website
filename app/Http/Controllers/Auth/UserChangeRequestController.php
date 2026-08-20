<?php

/**
 * GC-Stats — User change request tracking controller
 *
 * Lets a signed-in user follow the change requests they've submitted (see
 * PlayerChangeRequestController) — status, per-item accept/reject outcome,
 * and the moderation discussion, which they can also post into. Strictly
 * scoped to the user's own requests: every action here 403s on a
 * ChangeRequest whose requested_by isn't the current user, regardless of
 * subject — this is a "my submissions" view, not a moderation queue (see
 * App\Http\Controllers\Admin\ChangeRequestController for that).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Public\Controller;
use App\Models\ChangeRequest;
use App\Services\ChangeRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserChangeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = $request->user()->changeRequests()
            ->with(['subject', 'items'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('auth.change-requests.index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, ChangeRequest $changeRequest): View
    {
        $this->authorizeOwner($request, $changeRequest);

        $changeRequest->load(['subject', 'closedBy:id,name,username', 'items.resolvedBy:id,name,username', 'messages.user:id,name,username']);

        return view('auth.change-requests.show', [
            'request' => $changeRequest,
        ]);
    }

    public function storeMessage(Request $request, ChangeRequest $changeRequest, ChangeRequestService $changeRequests): RedirectResponse
    {
        $this->authorizeOwner($request, $changeRequest);

        abort_if($changeRequest->isResolved(), 403, 'This request has been resolved — the discussion is closed.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $changeRequests->addMessage($changeRequest, $request->user(), $validated['body']);

        return redirect()->route('account.change-requests.show', $changeRequest)->with('status', 'change-request-message-added');
    }

    private function authorizeOwner(Request $request, ChangeRequest $changeRequest): void
    {
        abort_unless($changeRequest->requested_by === $request->user()->id, 403, __('account.errors.not_own_change_request'));
    }
}
