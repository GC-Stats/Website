<?php

/**
 * GC-Stats — Game map round kill model
 *
 * Represents a single kill event within a round (killer, victim, time,
 * weapon, assistants). Persisted raw Riot data, used to derive clutch,
 * multi-kill, and trade stats at fetch time. Auto-fills its
 * tournament/phase/match from the parent round.
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

class GameMapRoundKill extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id', 'phase_id', 'match_id', 'game_map_round_id',
        'killer_player_id', 'victim_player_id', 'time_ms', 'weapon',
        'damage_type', 'is_secondary_fire', 'assistant_player_ids',
    ];

    protected $casts = [
        'assistant_player_ids' => 'array',
        'is_secondary_fire' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($kill) {
            static::resolveContextFromRound($kill);
        });
    }

    public function round()
    {
        return $this->belongsTo(GameMapRound::class, 'game_map_round_id');
    }

    public function killer()
    {
        return $this->belongsTo(Player::class, 'killer_player_id');
    }

    public function victim()
    {
        return $this->belongsTo(Player::class, 'victim_player_id');
    }
}
