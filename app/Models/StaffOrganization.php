<?php

/**
 * GC-Stats — Staff/Organization roster pivot model
 *
 * Roster-history row for a staff member's membership in an organization
 * (role, joined_at, left_at) — same shape as player_team, but unlike
 * player_team a staff member is allowed several concurrent active (left_at
 * null) memberships across different organizations, so nothing here closes
 * out a prior row automatically when a new one is added.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffOrganization extends Model
{
    protected $table = 'staff_organizations';

    protected $fillable = [
        'staff_id',
        'organization_id',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
