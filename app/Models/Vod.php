<?php

/**
 * GC-Stats — Match VOD model
 *
 * A recorded replay link (YouTube/Twitch/other) attached directly to a
 * match and, optionally, one specific map of that match (game_map_id null
 * = covers the whole match). Unlike StreamChannel, a VOD is not a reusable
 * entity — each row is a one-off link created for a single match, so there
 * is no separate "channel" CRUD, just Admin\MatchVodController's
 * store()/destroy(). publisher_id is nullable so a site admin can add a
 * VOD with no publisher attached, same pattern as StreamChannel/News.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vod extends Model
{
    protected $fillable = [
        'match_id',
        'game_map_id',
        'publisher_id',
        'url',
        'language_code',
    ];

    /**
     * Appended so it survives Matchs::toArray() — the public match page
     * caches the match payload as a plain array (see MatchController), so
     * platformIcon() itself wouldn't be reachable from the cached data.
     */
    protected $appends = ['icon'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(Matchs::class, 'match_id');
    }

    public function gameMap(): BelongsTo
    {
        return $this->belongsTo(GameMap::class, 'game_map_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(NewsPublisher::class, 'publisher_id');
    }

    /**
     * No platform column — the icon is detected from the URL itself
     * (YouTube/Twitch only; anything else shows no platform icon).
     */
    public function platformIcon(): ?string
    {
        return match (true) {
            str_contains($this->url, 'youtube.com'), str_contains($this->url, 'youtu.be') => 'fab-youtube',
            str_contains($this->url, 'twitch.tv') => 'fab-twitch',
            default => null,
        };
    }

    public function getIconAttribute(): ?string
    {
        return $this->platformIcon();
    }
}
