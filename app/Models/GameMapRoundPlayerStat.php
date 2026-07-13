<?php

/**
 * GC-Stats — Game map round player stat model
 *
 * Represents a single player's stats for a single round (kills, assists,
 * score, economy, weapon, armor). Auto-fills its tournament/phase/match
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

class GameMapRoundPlayerStat extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id', 'phase_id', 'match_id', 'game_map_round_id', 'player_id', 'kills', 'assists',
        'score', 'loadout_value', 'economy_spent', 'economy_remaining', 'weapon_id', 'armor',
    ];

    protected static function booted()
    {
        static::creating(function ($roundStat) {
            static::resolveContextFromRound($roundStat);
        });
    }

    public function round()
    {
        return $this->belongsTo(GameMapRound::class, 'game_map_round_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
