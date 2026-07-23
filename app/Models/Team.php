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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, HasLogo;

    protected $appends = ['logo'];

    protected $fillable = [
        'name',
        'country_code',
        'socials', 'tags', 'bio',
        'website',
        'short_name',
        'vlr_id',
        'is_active',
        'liquipedia_link',
        'max_permissions',
    ];

    protected $casts = [
        'socials' => 'array',
        'tags' => 'array',
        'max_permissions' => 'array',
    ];

    /**
     * Fan tags (e.g. "G2WIN") a user can pick to show off this team as
     * their "fan of" pick — see App\Models\User::team_tag.
     *
     * @return list<string>
     */
    public function fanTags(): array
    {
        return $this->tags ?? [];
    }

    /**
     * The ceiling of App\Support\TeamPermissions this team's own roles can
     * ever be granted, set by a site admin — see Admin\TeamController.
     *
     * @return list<string>
     */
    public function maxPermissions(): array
    {
        return $this->max_permissions ?? [];
    }

    /**
     * SEO-friendly URL segment for this team — not stored, derived from
     * the name (falls back to the id for names with no Latin-
     * transliterable characters). Every team-scoped management route
     * includes it after the id, matching the public team pages'
     * /team/{id}/{slug}/... convention.
     */
    public function routeSlug(): string
    {
        return Str::routeSlug($this->name, $this->id);
    }

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

    /** Every qualification/placement this team has ever satisfied — see PhaseQualificationResult. */
    public function qualificationResults(): MorphMany
    {
        return $this->morphMany(PhaseQualificationResult::class, 'entity');
    }

    /** This team's point ledger (signed entries) — see PointEntry. */
    public function pointEntries(): HasMany
    {
        return $this->hasMany(PointEntry::class);
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
