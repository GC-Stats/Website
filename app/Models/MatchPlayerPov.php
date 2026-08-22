<?php

/**
 * GC-Stats — Match Player POV model
 *
 * A Twitch channel detected as an official Player POV stream for a given
 * match: either a specific player's channel (player_id set) or a team's own
 * channel (player_id null), whose live title matched the tournament's
 * player_pov_phrase — see App\Console\Commands\ScheduledCommand\
 * DetectPlayerPovStreams. Upserted per (match_id, twitch_login), so
 * last_seen_live_at tracks the most recent detection sweep that still found
 * the channel live under a matching title.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPlayerPov extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'twitch_login',
        'title',
        'url',
        'last_seen_live_at',
    ];

    protected $casts = [
        'last_seen_live_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(Matchs::class, 'match_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
