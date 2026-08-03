<?php

/**
 * GC-Stats — Notification model
 *
 * An in-app notification delivered to a user (sanction issued, change
 * request comment, forum reply, ...). Distinct from Laravel's own
 * Illuminate\Notifications\DatabaseNotification (unused here — User's
 * Notifiable trait only drives Fortify's mail notifications): this is a
 * plain first-party model so it can carry a title/description/link/author
 * and be listed like any other resource, see NotificationService.
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

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'author_id',
        'type',
        'title',
        'description',
        'link',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Who triggered this notification — null for system-generated ones. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
