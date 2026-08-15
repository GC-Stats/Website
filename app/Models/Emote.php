<?php

/**
 * GC-Stats — Emote model
 *
 * A reaction image usable on reactable content (news, matches — reactions
 * themselves aren't built yet). The image either comes from an admin
 * upload (SVG/PNG/JPG), a frozen copy of a team's logo at creation time,
 * or the Twemoji SVG set imported in bulk (see
 * App\Console\Commands\ImportTwemojiEmotes — credited in README.md,
 * licensed CC-BY 4.0). All images are stored locally, never hotlinked.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Emote extends Model
{
    private const ACTIVE_CACHE_KEY = 'emotes:active';

    private const SOURCES_CACHE_KEY = 'emotes:sources';

    private const POPULAR_CACHE_KEY = 'emotes:popular';

    private const POPULAR_CACHE_TTL = 3600;

    protected $fillable = [
        'name',
        'image_path',
        'source',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Every active emote, cached — the catalog only changes through the
     * admin panel (Admin\EmoteController, which busts this cache on every
     * write), so there's no reason to hit the DB on each emote-picker
     * keystroke/render otherwise.
     *
     * @return Collection<int, self>
     */
    public static function active(): Collection
    {
        $cached = Cache::rememberForever(self::ACTIVE_CACHE_KEY, fn () => static::where('is_active', true)->orderBy('name')->get());

        // "forever" cache entries outlive deploys; a stale Redis entry can unserialize
        // as __PHP_Incomplete_Class if the class definition shifted underneath it.
        if (! $cached instanceof Collection) {
            self::forgetActiveCache();

            return static::where('is_active', true)->orderBy('name')->get();
        }

        return $cached;
    }

    /**
     * Every distinct `source` folder in use, cached the same way as
     * active() — used to build the admin emote list's source filter, which
     * is otherwise a full-table DISTINCT scan on every page load.
     *
     * @return Collection<int, string>
     */
    public static function sources(): Collection
    {
        $cached = Cache::rememberForever(self::SOURCES_CACHE_KEY, fn () => static::query()->distinct()->orderBy('source')->pluck('source'));

        if (! $cached instanceof Collection) {
            self::forgetSourcesCache();

            return static::query()->distinct()->orderBy('source')->pluck('source');
        }

        return $cached;
    }

    /**
     * Active emotes ordered by how often they've actually been used
     * (reaction count across every reactable — news, forum messages, ...),
     * ties broken alphabetically. Backs the emote picker so the most-used
     * emotes surface first instead of a flat alphabetical list. Cached for
     * an hour (not forever, unlike active()/sources()) since popularity
     * drifts continuously and there's no write path to bust it from, unlike
     * the admin-driven catalog changes those two react to.
     *
     * @return Collection<int, self>
     */
    public static function popular(): Collection
    {
        $cached = Cache::remember(self::POPULAR_CACHE_KEY, self::POPULAR_CACHE_TTL, fn () => static::where('is_active', true)
            ->withCount('reactions')
            ->orderByDesc('reactions_count')
            ->orderBy('name')
            ->get());

        if (! $cached instanceof Collection) {
            self::forgetPopularCache();

            return static::where('is_active', true)
                ->withCount('reactions')
                ->orderByDesc('reactions_count')
                ->orderBy('name')
                ->get();
        }

        return $cached;
    }

    public static function forgetActiveCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }

    public static function forgetSourcesCache(): void
    {
        Cache::forget(self::SOURCES_CACHE_KEY);
    }

    public static function forgetPopularCache(): void
    {
        Cache::forget(self::POPULAR_CACHE_KEY);
    }
}
