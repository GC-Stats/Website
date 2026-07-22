<?php

/**
 * GC-Stats — Phase qualification rule
 *
 * Declares that teams finishing a phase in a given rank range (swiss/round
 * robin) — or winning/losing a specific bracket match — either advance to
 * another tournament phase (possibly in a different tournament) or receive
 * a final placement (e.g. "Champion", "3-4").
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

class PhaseQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_phase_id',
        'rank_from',
        'rank_to',
        'source_match_id',
        'outcome',
        'destination_type',
        'destination_phase_id',
        'placement',
        'placement_label',
        'points',
        'cash_prize_amount',
        'cash_prize_currency',
    ];

    public function sourcePhase(): BelongsTo
    {
        return $this->belongsTo(TournamentPhase::class, 'source_phase_id');
    }

    public function sourceMatch(): BelongsTo
    {
        return $this->belongsTo(Matchs::class, 'source_match_id');
    }

    public function destinationPhase(): BelongsTo
    {
        return $this->belongsTo(TournamentPhase::class, 'destination_phase_id');
    }

    /** Which team(s) actually satisfied this rule — see PhaseQualificationResult. */
    public function results(): HasMany
    {
        return $this->hasMany(PhaseQualificationResult::class);
    }
}
