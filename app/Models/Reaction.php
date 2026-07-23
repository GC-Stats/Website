<?php

/**
 * GC-Stats — Reaction model
 *
 * A single user's emote reaction on a reactable model (News for now,
 * see App\Models\Concerns\HasReactions). One row per user/emote/content —
 * toggled on/off by App\Services\ReactionService rather than ever updated.
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

class Reaction extends Model
{
    protected $fillable = [
        'emote_id',
        'user_id',
        'reactable_id',
        'reactable_type',
    ];

    public function emote(): BelongsTo
    {
        return $this->belongsTo(Emote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }
}
