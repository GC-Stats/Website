<?php

/**
 * GC-Stats — Emailed user notification
 *
 * Mirrors an in-app Notification (see NotificationService) as an email —
 * dispatched only when the recipient has opted into the notification's
 * category, see EmailNotificationPreferences. One generic template for every
 * category, matching how NotificationService::notify() itself is type-
 * agnostic (title/description/link).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $link = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'title' => $this->title,
                'description' => $this->description,
                'link' => $this->link,
            ],
        );
    }
}
