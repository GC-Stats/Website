<?php

/**
 * GC-Stats — News article model
 *
 * Represents a published news article, optionally linked to players, teams
 * and tournaments, authored by a NewsAuthor and optionally tied to a NewsPublisher.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class News extends Model
{
    protected $fillable = [
        'author_id',
        'publisher_id',
        'lang',
        'title',
        'slug',
        'excerpt',
        'content',
        'image_cover',
        'status',
        'is_featured',
        'show_on_home',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'show_on_home' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(NewsAuthor::class, 'author_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(NewsPublisher::class, 'publisher_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(NewsImage::class);
    }

    public function players(): MorphToMany
    {
        return $this->morphedByMany(Player::class, 'relationable', 'news_relations');
    }

    public function teams(): MorphToMany
    {
        return $this->morphedByMany(Team::class, 'relationable', 'news_relations');
    }

    public function tournaments(): MorphToMany
    {
        return $this->morphedByMany(Tournament::class, 'relationable', 'news_relations');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeForLocale(Builder $query, string $lang): Builder
    {
        return $query->where('lang', $lang);
    }

    public function scopeOnHome(Builder $query): Builder
    {
        return $query->where('show_on_home', true);
    }
}
