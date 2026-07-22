<?php

/**
 * GC-Stats — Point ledger entry
 *
 * A signed points transaction for a team under a given point type — a
 * team's total is SUM(amount), not a mutable running counter, so a penalty
 * or correction is just another row instead of rewriting history.
 * `phase_qualification_result_id` links a system-generated entry back to
 * the placement that earned it (kept in sync by PhaseQualificationResolver);
 * left null for a manual bonus/penalty with no underlying result.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointEntry extends Model
{
    protected $fillable = [
        'team_id',
        'point_type_id',
        'amount',
        'phase_qualification_result_id',
        'reason',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function pointType(): BelongsTo
    {
        return $this->belongsTo(PointType::class);
    }

    public function phaseQualificationResult(): BelongsTo
    {
        return $this->belongsTo(PhaseQualificationResult::class);
    }
}
