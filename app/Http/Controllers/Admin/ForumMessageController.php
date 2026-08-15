<?php

/**
 * GC-Stats — Admin: recent forum messages feed
 *
 * A flat, reverse-chronological list of every forum message — unlike
 * App\Models\ModerationSuspect's queue (system-flagged only) or a thread's
 * own view (one thread at a time), this is the "check everything without
 * opening every forum" list moderators asked for. Hide/unhide/delete reuse
 * the same actions available from inside a thread (App\Services\
 * ForumService::deleteMessage(), ForumMessage::hidden_at), just reachable
 * without navigating there first.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\ForumMessage;
use App\Services\ForumService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForumMessageController extends Controller
{
    public function index(Request $request): View
    {
        $onlyHidden = $request->boolean('hidden');

        $messages = ForumMessage::with(['user:id,name,username', 'thread'])
            ->when($onlyHidden, fn ($query) => $query->whereNotNull('hidden_at'))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.forum.messages.index', [
            'messages' => $messages,
            'onlyHidden' => $onlyHidden,
        ]);
    }

    public function hide(Request $request, ForumMessage $forumMessage): RedirectResponse
    {
        $forumMessage->update(['hidden_at' => now()]);

        activity('moderation')
            ->performedOn($forumMessage)
            ->causedBy($request->user())
            ->log('forum.message_hidden');

        return back()->with('status', 'message-hidden');
    }

    public function unhide(Request $request, ForumMessage $forumMessage): RedirectResponse
    {
        $forumMessage->update(['hidden_at' => null]);

        activity('moderation')
            ->performedOn($forumMessage)
            ->causedBy($request->user())
            ->log('forum.message_unhidden');

        return back()->with('status', 'message-unhidden');
    }

    public function destroy(Request $request, ForumMessage $forumMessage, ForumService $forum): RedirectResponse
    {
        activity('moderation')
            ->performedOn($forumMessage)
            ->causedBy($request->user())
            ->log('forum.message_deleted');

        $forum->deleteMessage($forumMessage);

        return back()->with('status', 'message-deleted');
    }
}
