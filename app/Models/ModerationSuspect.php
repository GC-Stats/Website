<?php

/**
 * GC-Stats — Moderation suspect model
 *
 * A row logged by App\Jobs\ModerateForumMessage when a forum message trips
 * the OpenAI moderation check (App\Services\OpenAiModerationService).
 * Posting is never blocked — the message is always created, then
 * immediately hidden (see ForumMessage::hidden_at) pending a moderator's
 * review here. Distinct from UserReport: this is system-generated, not
 * user-submitted — see the moderation roadmap for why they're kept as
 * separate queues.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationSuspect extends Model
{
    public const STATUS_PENDING = 'pending';

    /** The flag was a false positive — the message was unhidden (approved). */
    public const STATUS_DISMISSED = 'dismissed';

    /** The flag was correct — the message stays hidden, case closed. */
    public const STATUS_ACTIONED = 'actioned';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_DISMISSED, self::STATUS_ACTIONED];

    protected $fillable = [
        'subject_type',
        'subject_id',
        'thread_id',
        'user_id',
        'matched_term',
        'body_snapshot',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
