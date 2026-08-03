<?php

/**
 * GC-Stats — Notification service
 *
 * Creates and manages in-app notifications (see App\Models\Notification).
 * Callers pass a free-form `type` slug (e.g. 'sanction.issued',
 * 'change_request.comment') used by the UI to pick an icon — add new ones
 * here as new events start notifying users.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Mail\UserNotificationMail;
use App\Models\Notification;
use App\Models\User;
use App\Support\EmailNotificationPreferences;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public const TYPE_SANCTION_ISSUED = 'sanction.issued';

    public const TYPE_CHANGE_REQUEST_COMMENT = 'change_request.comment';

    public const TYPE_CHANGE_REQUEST_ACCEPTED = 'change_request.accepted';

    public const TYPE_CHANGE_REQUEST_REJECTED = 'change_request.rejected';

    public const TYPE_CHANGE_REQUEST_WITHDRAWN = 'change_request.withdrawn';

    /**
     * Maps each notification type to the email-preference category that
     * gates whether it also gets emailed — see EmailNotificationPreferences.
     * A type with no entry here (e.g. a future 'social.*' type) is never
     * emailed even if a category toggle for it exists.
     */
    private const EMAIL_CATEGORIES = [
        self::TYPE_SANCTION_ISSUED => EmailNotificationPreferences::CATEGORY_SANCTION,
        self::TYPE_CHANGE_REQUEST_COMMENT => EmailNotificationPreferences::CATEGORY_CHANGE_REQUEST,
        self::TYPE_CHANGE_REQUEST_ACCEPTED => EmailNotificationPreferences::CATEGORY_CHANGE_REQUEST,
        self::TYPE_CHANGE_REQUEST_REJECTED => EmailNotificationPreferences::CATEGORY_CHANGE_REQUEST,
        self::TYPE_CHANGE_REQUEST_WITHDRAWN => EmailNotificationPreferences::CATEGORY_CHANGE_REQUEST,
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function notify(
        User $recipient,
        string $type,
        string $title,
        string $description,
        ?string $link = null,
        ?User $author = null,
        array $data = [],
    ): Notification {
        $notification = Notification::create([
            'user_id' => $recipient->id,
            'author_id' => $author?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'data' => $data,
        ]);

        $this->maybeSendEmail($recipient, $type, $title, $description, $link);

        return $notification;
    }

    private function maybeSendEmail(User $recipient, string $type, string $title, string $description, ?string $link): void
    {
        $category = self::EMAIL_CATEGORIES[$type] ?? null;

        if ($category === null || ! $recipient->email || ! EmailNotificationPreferences::enabled($recipient, $category)) {
            return;
        }

        Mail::to($recipient->email)->send(new UserNotificationMail(
            title: $title,
            description: $description,
            link: $link,
        ));
    }

    public function markAsRead(Notification $notification): void
    {
        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllAsRead(User $user): void
    {
        $user->notifications()->unread()->update(['read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }
}
