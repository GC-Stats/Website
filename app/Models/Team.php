<?php

/**
 * GC-Stats — Team model
 *
 * Represents a Valorant esports team (name, country, socials, bio, logo)
 * along with its roster, transactions and tournament participation.
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Team extends Model
{
    use HasFactory, HasLogo;

    protected $appends = ['logo'];

    protected $fillable = [
        'name',
        'country_code',
        'socials', 'bio',
        'website',
        'short_name',
        'vlr_id',
        'is_active',
        'liquipedia_link',
    ];

    protected $casts = [
        'socials' => 'array',
    ];

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_team')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    public function currentPlayers()
    {
        return $this->players()->wherePivot('left_at', null);
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_teams')
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

    public function getLogoAttribute(): string
    {
        return $this->resolveLogoUrl();
    }

    protected function logoStorageFolder(): string
    {
        return 'teams';
    }

    protected function defaultLogoUrl(): string
    {
        return asset('storage/images/default-team.webp');
    }
}
