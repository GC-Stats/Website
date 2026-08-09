<?php

/**
 * GC-Stats — News article model
 *
 * Represents a published news article, optionally linked to players, teams
 * and tournaments, authored by a NewsAuthor and attributed to either an
 * Organization or neither (a personal article, see the `news.author`
 * permission).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use App\Models\Concerns\HasReactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class News extends Model
{
    use HasReactions;

    protected $fillable = [
        'author_id',
        'organization_id',
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
        'scheduled_at',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'validated_at' => 'datetime',
        'is_featured' => 'boolean',
        'show_on_home' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(NewsAuthor::class, 'author_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NewsComment::class);
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

    public function staff(): MorphToMany
    {
        return $this->morphedByMany(Staff::class, 'relationable', 'news_relations');
    }

    /** Organizations tagged on this article — distinct from organization_id (who published it). */
    public function organizations(): MorphToMany
    {
        return $this->morphedByMany(Organization::class, 'relationable', 'news_relations');
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
