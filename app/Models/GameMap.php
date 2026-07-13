<?php

/**
 * GC-Stats — Game map model
 *
 * Represents a single map played within a match (score, order, completion
 * status). Auto-fills its tournament/phase from the parent match on creation.
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
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMap extends Model
{
    use HasFactory, ResolvesMatchContext;

    protected $fillable = ['tournament_id', 'phase_id', 'match_id', 'map_name', 'api_match_id', 'team_a_score', 'team_b_score', 'order', 'is_completed'];

    protected static function booted()
    {
        static::creating(function ($stat) {
            static::resolveContextFromMatch($stat);
        });
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(Matchs::class);
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(GamePlayerStat::class);
    }

    public function rounds()
    {
        return $this->hasMany(GameMapRound::class);
    }

    public function advancedStats(): HasMany
    {
        return $this->hasMany(GamePlayerAdvancedStat::class);
    }
}
