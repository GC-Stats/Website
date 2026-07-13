<?php

/**
 * GC-Stats — Game player stat model
 *
 * Represents a player's overall statistics for a single map (agent, kills,
 * deaths, assists, ACS, ADR, KAST, first kills/deaths, headshot %).
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

class GamePlayerStat extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = [
        'tournament_id', 'phase_id', 'game_map_id', 'player_id', 'team_id', 'agent_name', 'val_name', 'match_id',
        'kills', 'deaths', 'assists', 'acs', 'adr',
        'kast_percentage', 'first_kills', 'first_deaths', 'headshot_percentage',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
