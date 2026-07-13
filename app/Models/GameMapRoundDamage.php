<?php

/**
 * GC-Stats — Game map round damage model
 *
 * Represents a single attacker/receiver damage entry within a round
 * (damage, headshots, bodyshots, legshots). Persisted raw Riot data.
 * Auto-fills its tournament/phase/match from the parent round.
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

class GameMapRoundDamage extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id', 'phase_id', 'match_id', 'game_map_round_id',
        'attacker_player_id', 'receiver_player_id', 'damage',
        'headshots', 'bodyshots', 'legshots',
    ];

    protected static function booted()
    {
        static::creating(function ($damage) {
            static::resolveContextFromRound($damage);
        });
    }

    public function round()
    {
        return $this->belongsTo(GameMapRound::class, 'game_map_round_id');
    }

    public function attacker()
    {
        return $this->belongsTo(Player::class, 'attacker_player_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Player::class, 'receiver_player_id');
    }
}
