<?php

/**
 * GC-Stats — News image model
 *
 * Represents a locally-stored image (WebP) uploaded for a news article.
 * Optionally linked to a news article and tracks the uploader.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsImage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'news_id',
        'author_id',
    ];

    protected $appends = ['url'];

    protected static function booted(): void
    {
        static::creating(function (NewsImage $image) {
            // The id is used as the storage directory name, so it must always
            // be a server-generated UUID — never an arbitrary caller-supplied value.
            if (! $image->id || ! Str::isUuid($image->id)) {
                $image->id = (string) Str::uuid();
            }
        });
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(NewsAuthor::class, 'author_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url("news/{$this->id}/cover.webp");
    }
}
