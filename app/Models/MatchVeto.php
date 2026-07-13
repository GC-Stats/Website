<?php

/**
 * GC-Stats — Match veto model
 *
 * Represents a single map pick/ban/side-selection action made during a
 * match's veto phase, including which team made the pick and which side
 * was chosen.
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

class MatchVeto extends Model
{
    use HasFactory;

    protected $fillable = ['match_id', 'team_id', 'map_name', 'type', 'side', 'side_picked_by', 'order'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(Matchs::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sidePickedBy()
    {
        return $this->belongsTo(Team::class, 'side_picked_by');
    }
}
