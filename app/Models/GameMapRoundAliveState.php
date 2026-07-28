<?php

/**
 * GC-Stats — Game map round alive-state model
 *
 * Represents a single ATK-vs-DEF alive-player-count transition within a
 * round (a "XvY" situation) — one row for the round's initial 5v5 state
 * plus one row per kill thereafter. The round's outcome is denormalized
 * onto every row as winner_side so situational winrate queries (e.g.
 * "winrate at 4v4") never need a join. Auto-fills its tournament/phase/match
 * from the parent round.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use App\Models\Concerns\ResolvesMatchContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameMapRoundAliveState extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id', 'phase_id', 'match_id', 'game_map_round_id',
        'sequence', 'time_ms', 'atk_alive', 'def_alive', 'winner_side',
    ];

    protected static function booted()
    {
        static::creating(function ($state) {
            static::resolveContextFromRound($state);
        });
    }

    public function round()
    {
        return $this->belongsTo(GameMapRound::class, 'game_map_round_id');
    }
}
