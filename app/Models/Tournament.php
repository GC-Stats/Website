<?php

/**
 * GC-Stats — Tournament model
 *
 * Represents a Valorant esports tournament (name, region, category, dates,
 * location, prize pool, logo) along with its phases, teams and matches.
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

class Tournament extends Model
{
    use HasFactory, HasLogo;

    protected $appends = ['logo'];

    protected $fillable = [
        'name',
        'slug',
        'region',
        'category',
        'start_date',
        'end_date',
        'location',
        'prize_pool',
        'description',
        'status',
        'active',
        'liquipedia_link',
        'point_type_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function tournamentPhases()
    {
        return $this->hasMany(TournamentPhase::class);
    }

    public function rootPhases()
    {
        return $this->hasMany(TournamentPhase::class)
            ->where(function ($query) {
                $query->whereNull('parent_id')
                    ->orWhere('parent_id', 0);
            })
            ->orderBy('order', 'asc');
    }

    public function phases()
    {
        return $this->hasMany(TournamentPhase::class);
    }

    /**
     * Every phase, flattened depth-first so each phase is immediately
     * followed by its own children (recursively) rather than in raw id
     * order — the ordering admin phase dropdowns need to display child
     * options directly under their parent, as in the tournament edit form.
     */
    public function orderedPhases()
    {
        $byParent = $this->tournamentPhases()
            ->orderBy('order')
            ->get()
            ->groupBy(fn ($phase) => $phase->parent_id ?: 0);

        $walk = function ($parentId) use (&$walk, $byParent) {
            return ($byParent->get($parentId) ?? collect())
                ->flatMap(fn ($phase) => collect([$phase])->merge($walk($phase->id)));
        };

        return $walk(0);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'tournament_teams')
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(Matchs::class);
    }

    public function pointType(): BelongsTo
    {
        return $this->belongsTo(PointType::class);
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
        return 'tournaments';
    }

    protected function defaultLogoUrl(): string
    {
        return asset('storage/images/default-tournament.webp');
    }
}
