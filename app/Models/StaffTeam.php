<?php

/**
 * GC-Stats — Staff/team roster pivot model
 *
 * Roster-history row for a staff member working directly for a team, with
 * no organization involved (role, joined_at, left_at) — same shape as
 * StaffOrganization/player_team. Mirrors StaffOrganization's "several
 * concurrent active rows allowed" behaviour: nothing here closes out a
 * prior row automatically.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTeam extends Model
{
    protected $table = 'staff_teams';

    protected $fillable = [
        'staff_id',
        'team_id',
        'role',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'left_at' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
