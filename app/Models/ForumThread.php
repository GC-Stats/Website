<?php

/**
 * GC-Stats — Forum thread model
 *
 * A discussion thread, either "general" (free-standing, user-created) or
 * attached to a subject (Tournament, Matchs, News) via a nullable morph —
 * see App\Services\ForumService::findOrCreateThreadFor(), which lazily
 * creates the one thread a given subject can have (enforced by a unique
 * index on subject_type/subject_id).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumThread extends Model
{
    public const CATEGORY_TOURNAMENT = 'tournament';

    public const CATEGORY_MATCH = 'match';

    public const CATEGORY_NEWS = 'news';

    public const CATEGORY_GENERAL = 'general';

    public const CATEGORIES = [
        self::CATEGORY_TOURNAMENT,
        self::CATEGORY_MATCH,
        self::CATEGORY_NEWS,
        self::CATEGORY_GENERAL,
    ];

    protected $fillable = [
        'category',
        'subject_type',
        'subject_id',
        'title',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ForumMessage::class, 'thread_id');
    }
}
