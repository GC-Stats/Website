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
}
