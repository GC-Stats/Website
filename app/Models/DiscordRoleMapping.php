<?php

/**
 * GC-Stats — DiscordRoleMapping model
 *
 * Maps a Discord guild role to an application role (optionally scoped to a
 * team), driving the auto-role sync performed by DiscordRoleSyncService.
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

class DiscordRoleMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'discord_role_id',
        'discord_role_name',
        'app_role',
        'team_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
