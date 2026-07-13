<?php

/**
 * GC-Stats — Match model
 *
 * Represents a single match between two teams within a tournament phase
 * (schedule, status, scores, round/best-of info). Backed by the "matches"
 * table.
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
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matchs extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'tournament_id', 'phase_id', 'team_a_id', 'team_b_id',
        'scheduled_at', 'status', 'team_a_score', 'team_b_score', 'patch',
        'match_order', 'round_name', 'round_number', 'best_of',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function teamA()
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    public function teamB()
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    public function map_bans(): HasMany
    {
        return $this->hasMany(MatchVeto::class, 'match_id');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    // Only relation to game maps used across the app (controllers/commands);
    // the previous camelCase `maps()` alias was unused and has been removed.
    public function game_maps()
    {
        return $this->hasMany(GameMap::class, 'match_id');
    }

    public function tournamentPhase()
    {
        return $this->belongsTo(TournamentPhase::class, 'phase_id');
    }

    public function getResultForTeam($teamId)
    {
        if (is_null($this->team_a_score) || is_null($this->team_b_score)) {
            return 'pending';
        }

        $scoreA = (int) $this->team_a_score;
        $scoreB = (int) $this->team_b_score;

        if ($scoreA === $scoreB) {
            return 'draw';
        }

        $isTeamA = ($this->team_a_id == $teamId);
        $won = $isTeamA ? ($scoreA > $scoreB) : ($scoreB > $scoreA);

        return $won ? 'win' : 'loss';
    }

    public function gamePlayerStats()
    {
        return $this->hasMany(GamePlayerStat::class, 'match_id');
    }
}
