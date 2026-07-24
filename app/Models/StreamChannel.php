<?php

/**
 * GC-Stats — Stream channel model
 *
 * A streaming channel (YouTube/Twitch/TikTok) that can be linked to one or
 * more matches (see Matchs::streams()). Optionally owned by a NewsPublisher
 * — publisher_id is nullable so a site admin can create a channel with no
 * publisher attached, same pattern as News::publisher_id.
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StreamChannel extends Model
{
    public const PLATFORM_YOUTUBE = 'youtube';

    public const PLATFORM_TWITCH = 'twitch';

    public const PLATFORM_TIKTOK = 'tiktok';

    public const PLATFORMS = [self::PLATFORM_YOUTUBE, self::PLATFORM_TWITCH, self::PLATFORM_TIKTOK];

    protected $fillable = [
        'publisher_id',
        'name',
        'platform',
        'url',
        'language_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Appended so it survives Matchs::toArray() — the public match page
     * caches the match payload as a plain array (see MatchController), so
     * the icon() method itself wouldn't be reachable from the cached data.
     */
    protected $appends = ['icon'];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(NewsPublisher::class, 'publisher_id');
    }

    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(Matchs::class, 'match_streams', 'stream_channel_id', 'match_id')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * blade-icons key for this channel's platform — same fab-* icon set
     * used for social links in resources/views/team/header.blade.php.
     */
    public function icon(): string
    {
        return match ($this->platform) {
            self::PLATFORM_YOUTUBE => 'fab-youtube',
            self::PLATFORM_TWITCH => 'fab-twitch',
            self::PLATFORM_TIKTOK => 'fab-tiktok',
            default => 'fas-globe',
        };
    }

    public function getIconAttribute(): string
    {
        return $this->icon();
    }
}
