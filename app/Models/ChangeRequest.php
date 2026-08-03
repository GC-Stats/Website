<?php

/**
 * GC-Stats — ChangeRequest model
 *
 * A proposed edit to a polymorphic subject (team, player, tournament, ...),
 * made of one or more ChangeRequestItem rows (one per field) that can each
 * be accepted or rejected independently. Requests are never deleted —
 * closing one only updates its status. requested_by is null for
 * system-generated requests (e.g. a roster mismatch detected while fetching
 * match stats), same nullable-actor convention as UserReport/Sanction.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChangeRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_AWAITING_REQUESTER_REPLY = 'awaiting_requester_reply';

    public const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_AWAITING_REQUESTER_REPLY,
        self::STATUS_PARTIALLY_ACCEPTED,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_WITHDRAWN,
    ];

    protected $fillable = [
        'subject_type',
        'subject_id',
        'requested_by',
        'reason',
        'status',
        'closed_by',
        'closed_at',
        'sanctioned_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'sanctioned_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChangeRequestItem::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChangeRequestMessage::class)->orderBy('created_at');
    }

    public function isSystemGenerated(): bool
    {
        return $this->requested_by === null;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_WITHDRAWN], true);
    }

    /**
     * True once every item has a final decision — no item left pending —
     * regardless of whether the outcome was uniform (isClosed(): accepted/
     * rejected/withdrawn) or mixed (partially_accepted, per
     * ChangeRequestService::refreshStatus() only reached once nothing is
     * pending anymore). awaiting_requester_reply is deliberately excluded:
     * it's a discussion-still-open sub-state of pending (see
     * ChangeRequestService::maybeAwaitRequesterReply()), not a resolution.
     * Used to close the discussion thread: once nothing is left to decide,
     * there's nothing left to discuss.
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_WITHDRAWN, self::STATUS_PARTIALLY_ACCEPTED], true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_AWAITING_REQUESTER_REPLY, self::STATUS_PARTIALLY_ACCEPTED]);
    }
}
