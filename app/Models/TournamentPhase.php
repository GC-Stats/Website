<?php

/**
 * GC-Stats — Tournament phase model
 *
 * Represents a stage of a tournament (e.g. group stage, playoffs) with a
 * format and ordering, optionally nested under a parent phase.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentPhase extends Model
{
    use HasFactory;

    /** Formats whose standings are ranked (not bracket-based) and support rank-range qualification rules. */
    public const RANK_BASED_FORMATS = ['swiss', 'round_robin', 'swiss_buchholz'];

    protected $fillable = [
        'tournament_id',
        'name',
        'format',
        'order',
        'parent_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(Matchs::class, 'phase_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TournamentPhase::class, 'parent_id')
            ->orderBy('order');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TournamentPhase::class, 'parent_id');
    }

    /** Rank-range qualification rules sourced from this phase (swiss/round_robin). */
    public function qualifications(): HasMany
    {
        return $this->hasMany(PhaseQualification::class, 'source_phase_id')
            ->orderBy('rank_from');
    }

    /** Qualification rules that send teams into this phase from elsewhere. */
    public function incomingQualifications(): HasMany
    {
        return $this->hasMany(PhaseQualification::class, 'destination_phase_id');
    }

    /**
     * This phase's id plus every descendant's, at any depth — used to scope
     * "matches of this phase" filters so picking a parent phase also
     * surfaces matches sitting on its sub-phases (and their own sub-phases).
     */
    public function selfAndDescendantIds(): array
    {
        $byParent = static::where('tournament_id', $this->tournament_id)
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $ids = [$this->id];
        $queue = [$this->id];

        while ($queue) {
            $current = array_shift($queue);

            foreach ($byParent->get($current) ?? [] as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }
}
