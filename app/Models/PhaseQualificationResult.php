<?php

/**
 * GC-Stats — Resolved qualification result
 *
 * A (rule, entity) pair recording that a specific team/player/etc satisfied
 * a PhaseQualification rule — e.g. rule "rank 1-2 qualify for X" is
 * satisfied by two different teams, each getting its own row here, and a
 * team result also produces one row per player who was on that team's
 * roster for the tournament (via game_player_stats). Polymorphic so the
 * same table covers teams, players, and later coaches/managers/etc without
 * another migration — entity_type uses the app's Relation::morphMap
 * aliases ('team', 'player', ...).
 *
 * Kept continuously up to date (not a one-time snapshot) by
 * PhaseQualificationResolver, triggered whenever a relevant match or rule
 * is saved. This is what lets a team/player profile query "every
 * qualification/placement ever earned" with a single indexed lookup
 * instead of recomputing standings for every rule site-wide.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PhaseQualificationResult extends Model
{
    protected $fillable = [
        'phase_qualification_id',
        'entity_type',
        'entity_id',
        'rank',
    ];

    public function phaseQualification(): BelongsTo
    {
        return $this->belongsTo(PhaseQualification::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /** The team-side point ledger entry this result generated, if the rule awards points. */
    public function pointEntry(): HasOne
    {
        return $this->hasOne(PointEntry::class);
    }
}
