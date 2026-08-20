<?php

/**
 * GC-Stats — Notification controller
 *
 * Lets a signed-in user browse their own in-app notifications (see
 * NotificationService) and mark them read. Strictly scoped to the current
 * user's own notifications.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Public\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Support\EmailNotificationPreferences;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()
            ->with('author:id,name,username')
            ->when($request->boolean('unread'), fn ($query) => $query->unread())
            ->paginate(20)
            ->withQueryString();

        return view('auth.notifications.index', [
            'notifications' => $notifications,
            'emailCategories' => EmailNotificationPreferences::CATEGORIES,
        ]);
    }

    public function markAllRead(Request $request, NotificationService $notifications): RedirectResponse
    {
        $notifications->markAllAsRead($request->user());

        return back();
    }

    public function updateEmailPreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['array'],
            'categories.*' => ['string', 'in:'.implode(',', EmailNotificationPreferences::CATEGORIES)],
        ]);

        EmailNotificationPreferences::update($request->user(), $validated['categories'] ?? []);

        return back()->with('status', 'email-preferences-updated');
    }

    /**
     * Marks the notification read, then redirects to its target link — the
     * single entry point every notification click (bell dropdown or full
     * list) goes through, so "read" and "navigate" can never happen apart.
     */
    public function open(Request $request, Notification $notification, NotificationService $notifications): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403, __('account.errors.not_own_notification'));

        $notifications->markAsRead($notification);

        return redirect($notification->link ?? route('account.notifications.index'));
    }
}
