<?php

/**
 * GC-Stats — Game map round model
 *
 * Represents a single round within a game map (round number, winning team,
 * win type). Auto-fills its tournament/phase/match from the parent game map.
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

class GameMapRound extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id',
        'phase_id',
        'match_id',
        'game_map_id',
        'round_number',
        'winning_team',
        'win_type',
    ];

    protected static function booted()
    {
        static::creating(function ($stat) {
            static::resolveContextFromGameMap($stat);
        });
    }

    public function gameMap()
    {
        return $this->belongsTo(GameMap::class);
    }

    public function playerStats()
    {
        return $this->hasMany(GameMapRoundPlayerStat::class);
    }

    public function kills()
    {
        return $this->hasMany(GameMapRoundKill::class);
    }

    public function damages()
    {
        return $this->hasMany(GameMapRoundDamage::class);
    }
}
