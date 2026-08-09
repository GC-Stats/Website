<?php

/**
 * GC-Stats — News review comment
 *
 * One entry in an organization's news article review discussion — see the
 * 0121_create_news_comments_table migration's docblock for why this is a
 * flat thread with an optional `field` anchor rather than a character-range
 * diff. `type` distinguishes a human `comment` from an auto-generated
 * `system` entry (e.g. "Validation reset after edit"), mirroring
 * ChangeRequestMessage.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsComment extends Model
{
    protected $fillable = [
        'news_id',
        'user_id',
        'field',
        'body',
        'type',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
