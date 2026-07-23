<?php

/**
 * GC-Stats — UserReport model
 *
 * A user-submitted flag on another user's account ("déclarer un utilisateur
 * suspect"), queued for moderation review. Persists through the reported
 * account's own deletion (nullOnDelete), same anti-erasure logic as
 * Sanction.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class UserReport extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_ACTIONED = 'actioned';

    public const STATUS_DISMISSED = 'dismissed';

    public const CATEGORIES = ['fraud', 'ban_evasion', 'harassment', 'fake_account', 'other'];

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'team_id',
        'reactable_type',
        'reactable_id',
        'emote_id',
        'category',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'resolution_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function emote(): BelongsTo
    {
        return $this->belongsTo(Emote::class);
    }

    /**
     * A reaction report concerns every current reactor of the flagged emote
     * rather than a single reported_user_id — see UserReportService::submitForReaction().
     * Checked via reactable_type rather than emote_id since emote_id is
     * nulled (nullOnDelete) if the flagged emote is later deleted, but the
     * report should still render as a reaction report, not silently fall
     * back to the "reported user" layout.
     */
    public function isReactionReport(): bool
    {
        return $this->reactable_type !== null;
    }

    /**
     * Live list of every user currently using the flagged emote on the
     * flagged reactable — computed on demand rather than snapshotted at
     * report time, so it stays accurate as reactors change. Empty if the
     * flagged emote was since deleted (emote_id nulled).
     *
     * @return Collection<int, Reaction>
     */
    public function reactingUsers(): Collection
    {
        if (! $this->isReactionReport() || $this->emote_id === null) {
            return collect();
        }

        return Reaction::where('reactable_type', $this->reactable_type)
            ->where('reactable_id', $this->reactable_id)
            ->where('emote_id', $this->emote_id)
            ->with('user')
            ->get();
    }
}
