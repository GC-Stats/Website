<?php

/**
 * GC-Stats — News publisher model
 *
 * Represents a publisher (outlet, channel, etc.) that produces
 * news articles published on GC-Stats.
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

class NewsPublisher extends Model
{
    use HasLogo;

    protected $table = 'news_publishers';

    protected $appends = ['logo'];

    protected $fillable = [
        'name',
        'slug',
        'socials',
        'max_permissions',
    ];

    protected $casts = [
        'socials' => 'array',
        'max_permissions' => 'array',
    ];

    /**
     * The ceiling of App\Support\PublisherPermissions this publisher's own
     * roles can ever be granted, set by a site admin — see
     * Admin\NewsPublisherController.
     *
     * @return list<string>
     */
    public function maxPermissions(): array
    {
        return $this->max_permissions ?? [];
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'publisher_id');
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
        return 'publishers';
    }

    protected function defaultLogoUrl(): string
    {
        return '';
    }
}
