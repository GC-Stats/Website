<?php

/**
 * GC-Stats — Game map round player position model
 *
 * Represents a single player's position snapshot within a round, taken at
 * a kill (all alive players, from Riot's playerLocations), a plant, or a
 * defuse. Persisted raw Riot data. Auto-fills its tournament/phase/match
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

class GameMapRoundPlayerPosition extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id', 'phase_id', 'match_id', 'game_map_round_id',
        'event_type', 'game_map_round_kill_id', 'player_id', 'role',
        'x', 'y', 'view_radians', 'time_ms',
    ];

    protected static function booted()
    {
        static::creating(function ($position) {
            static::resolveContextFromRound($position);
        });
    }

    public function round()
    {
        return $this->belongsTo(GameMapRound::class, 'game_map_round_id');
    }

    public function kill()
    {
        return $this->belongsTo(GameMapRoundKill::class, 'game_map_round_kill_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
