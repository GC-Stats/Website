<?php

/**
 * GC-Stats — ChangeRequestItem model
 *
 * A single proposed field change within a ChangeRequest. `field` names both
 * the value being changed and the FieldApplier used to apply it once
 * accepted, see ChangeRequestApplierRegistry. `status` is decided
 * independently per item (partial acceptance); `applied_at`/`apply_error`
 * track whether an accepted item was actually applied, since the human
 * decision and the mechanical application are separate steps that can fail
 * independently.
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

class ChangeRequestItem extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'change_request_id',
        'field',
        'old_value',
        'new_value',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
        'applied_at',
        'apply_error',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'resolved_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isApplied(): bool
    {
        return $this->applied_at !== null;
    }
}
