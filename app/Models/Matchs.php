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

use App\Support\CurrentTheme;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /** Winner/loser qualification rules attached to this bracket match. */
    public function qualifications(): HasMany
    {
        return $this->hasMany(PhaseQualification::class, 'source_match_id');
    }

    public function streams(): BelongsToMany
    {
        return $this->belongsToMany(StreamChannel::class, 'match_streams', 'match_id', 'stream_channel_id')
            ->withTimestamps();
    }

    public function vods(): HasMany
    {
        return $this->hasMany(Vod::class, 'match_id');
    }

    /**
     * The array shape resources/views/components/match/score-header.blade.php
     * needs — single source of truth for the time-aware team name/logo
     * resolution (Team::nameAt()/logoAt(), since a team's name/logo can
     * change after this match was played) shared by
     * App\Http\Controllers\Public\MatchController and by forum match
     * embeds (see App\Models\ForumMessage::resolveEmbed()).
     *
     * @return array{id: int, tournament: ?array, tournament_phase: ?array, team_a_id: ?int, team_b_id: ?int, team_a_data: ?array, team_b_data: ?array, team_a_score: ?int, team_b_score: ?int, status: string, patch: ?string, scheduled_at: mixed}
     */
    public function toScoreHeaderArray(): array
    {
        return [
            'id' => $this->id,
            'tournament' => $this->tournament ? ['id' => $this->tournament->id, 'name' => $this->tournament->name] : null,
            'tournament_phase' => $this->tournamentPhase ? ['id' => $this->tournamentPhase->id, 'name' => $this->tournamentPhase->name] : null,
            'team_a_id' => $this->team_a_id,
            'team_b_id' => $this->team_b_id,
            'team_a_data' => $this->teamA ? [
                ...$this->teamA->only(['id', 'name', 'short_name']),
                'name' => $this->teamA->nameAt($this->scheduled_at),
                'logo' => $this->teamA->logoAt($this->scheduled_at, CurrentTheme::get()),
            ] : null,
            'team_b_data' => $this->teamB ? [
                ...$this->teamB->only(['id', 'name', 'short_name']),
                'name' => $this->teamB->nameAt($this->scheduled_at),
                'logo' => $this->teamB->logoAt($this->scheduled_at, CurrentTheme::get()),
            ] : null,
            'team_a_score' => $this->team_a_score,
            'team_b_score' => $this->team_b_score,
            'status' => $this->status,
            'patch' => $this->patch,
            'scheduled_at' => $this->scheduled_at,
        ];
    }
}
