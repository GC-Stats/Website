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

    protected $fillable = [
        'tournament_id',
        'name',
        'format',
        'order',
        'parent_id',
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
        // Intentionally no ->with(['children', 'matches']) here: eager-loading
        // the relation recursively inside its own definition causes unbounded
        // N+1 loading as soon as children is traversed more than once in a
        // deep bracket tree. Callers that need the phase tree must load it
        // explicitly with a bounded depth, e.g.
        // TournamentPhase::with(['children.matches', 'children.children.matches'])->...
        return $this->hasMany(TournamentPhase::class, 'parent_id')
            ->orderBy('order');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TournamentPhase::class, 'parent_id');
    }
}
