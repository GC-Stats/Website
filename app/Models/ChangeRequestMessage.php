<?php

/**
 * GC-Stats — ChangeRequestMessage model
 *
 * A discussion entry on a ChangeRequest: either a human comment (`user_id`
 * set) or a system note (`user_id` null, e.g. "item #3 accepted by ...").
 * Never deleted; edits only set `edited_at`.
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

class ChangeRequestMessage extends Model
{
    use HasFactory;

    public const TYPE_COMMENT = 'comment';

    public const TYPE_SYSTEM = 'system';

    public const TYPES = [
        self::TYPE_COMMENT,
        self::TYPE_SYSTEM,
    ];

    protected $fillable = [
        'change_request_id',
        'user_id',
        'type',
        'body',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
