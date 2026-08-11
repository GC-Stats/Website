<?php

/**
 * GC-Stats — Team name history model
 *
 * Represents a name a team was known under during a given time range
 * (from/until), allowing the correct name to be shown for past matches.
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

class TeamNameHistory extends Model
{
    use HasFactory;

    protected $table = 'team_name_history';

    // Named is_visible, not visible — Eloquent's Model base class already
    // declares a protected $visible property (attribute serialization
    // whitelist), which silently shadows a same-named column when accessed
    // from another Model subclass's scope (e.g. Team::nameAt()).
    protected $fillable = ['team_id', 'name', 'from', 'until', 'is_visible'];

    protected $casts = [
        'from' => 'date',
        'until' => 'date',
        'is_visible' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
