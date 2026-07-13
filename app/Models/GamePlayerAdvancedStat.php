<?php

/**
 * GC-Stats — Game player advanced stat model
 *
 * Represents a player's advanced statistics for a single map (clutches,
 * multi-kills, trades, economy/post-plant, ATK/DEF splits). Computed and
 * persisted at fetch time from raw Riot round/kill/damage data.
 * Auto-fills its tournament/phase from the parent match on creation.
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlayerAdvancedStat extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id', 'phase_id', 'match_id', 'game_map_id', 'player_id', 'agent_name',
        'clutch_1v1_won', 'clutch_1v1_total',
        'clutch_1v2_won', 'clutch_1v2_total',
        'clutch_1v3_won', 'clutch_1v3_total',
        'clutch_1v4_won', 'clutch_1v4_total',
        'clutch_1v5_won', 'clutch_1v5_total',
        'multikill_2k', 'multikill_3k', 'multikill_4k', 'multikill_5k',
        'trade_kills', 'traded_deaths',
        'plants', 'defuses',
        'pistol_won', 'pistol_played',
        'eco_won', 'eco_played',
        'force_won', 'force_played',
        'full_buy_won', 'full_buy_played',
        'post_plant_won', 'post_plant_played',
        'atk_rounds', 'atk_rounds_won', 'atk_kills', 'atk_kast_percentage',
        'def_rounds', 'def_rounds_won', 'def_kills', 'def_kast_percentage',
    ];

    protected static function booted()
    {
        static::creating(function ($stat) {
            static::resolveContextFromMatch($stat);
        });
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function gameMap(): BelongsTo
    {
        return $this->belongsTo(GameMap::class);
    }
}
