<?php

/**
 * GC-Stats — Point type
 *
 * One row per named points system + validity period a tournament can be
 * bound to (e.g. "Cash Cup Points" has a 2025 row and a 2026 row, sharing
 * the same name but with their own label/date range) — a tournament binds
 * to one row directly via tournaments.point_type_id.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointType extends Model
{
    protected $fillable = ['name', 'label', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PointEntry::class);
    }
}
