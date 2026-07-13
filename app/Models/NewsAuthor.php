<?php

/**
 * GC-Stats — News author model
 *
 * Represents an author who writes news articles, with their social links
 * and public profile information.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use App\Models\Concerns\HasLogo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NewsAuthor extends Model
{
    use HasLogo;

    protected $table = 'news_authors';

    protected $appends = ['logo'];

    protected $fillable = [
        'name',
        'slug',
        'bio',
        'socials',
    ];

    protected $casts = [
        'socials' => 'array',
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'author_id');
    }

    public function logos(): MorphMany
    {
        return $this->morphMany(Logo::class, 'entity');
    }

    public function getLogoAttribute(): string
    {
        return $this->resolveLogoUrl();
    }

    protected function logoStorageFolder(): string
    {
        return 'authors';
    }

    protected function defaultLogoUrl(): string
    {
        return '';
    }
}
