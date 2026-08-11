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
use App\Support\CurrentTheme;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
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

    public function nameHistory(): HasMany
    {
        return $this->hasMany(TeamNameHistory::class)->orderByDesc('from');
    }

    /**
     * The name this team was known under at a given point in time. Reads
     * from the already-loaded nameHistory collection when available (so
     * eager-loading it avoids N+1 across a list of matches), falling back
     * to a fresh query otherwise. Teams that have never been renamed have
     * no history rows at all, so the live `name` column is always the
     * correct fallback — as does a matching entry an admin marked
     * `is_visible = false` (e.g. a typo fix that shouldn't retroactively
     * change past match displays).
     */
    public function nameAt(CarbonInterface|string $date): string
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        $entry = $this->nameHistory
            ->first(fn (TeamNameHistory $entry) => $entry->is_visible && $entry->from->lte($date) && (! $entry->until || $entry->until->gte($date)));

        return $entry->name ?? $this->name;
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
        return $this->resolveLogoUrl(CurrentTheme::get());
    }

    protected function logoStorageFolder(): string
    {
        return 'teams';
    }

    protected function defaultLogoUrl(?string $theme = null): string
    {
        return match ($theme) {
            'dark' => asset('storage/images/default-team-dark.webp'),
            'light' => asset('storage/images/default-team-light.webp'),
            default => asset('storage/images/default-team.webp'),
        };
    }
}
