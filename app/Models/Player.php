<?php

/**
 * GC-Stats — Player model
 *
 * Represents a Valorant esports player (handle, name, country, bio, photo,
 * socials, VLR/Riot IDs) and their team history and statistics.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use App\Models\Concerns\HasLogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Player extends Model
{
    use HasFactory, HasLogo;

    protected $appends = ['profile_photo'];

    protected $fillable = [
        'user_id',
        'handle',
        'first_name',
        'last_name',
        'country_code',
        'bio',
        'photo_url',
        'discord_id',
        'socials',
        'is_active',
        'vlr_id',
        'val_id',
        'liquipedia_link',
    ];

    protected $casts = [
        'socials' => 'array',
    ];

    public function stats(): HasMany
    {
        return $this->hasMany(GamePlayerStat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'player_team')
            ->withPivot('role', 'joined_at', 'left_at')
            ->orderBy('joined_at', 'desc')
            ->withTimestamps();
    }

    public function news()
    {
        return $this->morphToMany(News::class, 'relationable', 'news_relations', 'relationable_id', 'news_id');
    }

    public function logos(): MorphMany
    {
        return $this->morphMany(Logo::class, 'entity');
    }

    public function getProfilePhotoAttribute(): string
    {
        return $this->resolveLogoUrl();
    }

    protected function logoStorageFolder(): string
    {
        return 'players';
    }

    protected function defaultLogoUrl(): string
    {
        return '';
    }
}
