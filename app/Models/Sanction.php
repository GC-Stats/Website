<?php

/**
 * GC-Stats — Sanction model
 *
 * Represents a moderation sanction (warning, mute, suspension or ban) issued
 * against a user, either globally or scoped to a team. Sanctions survive the
 * deletion of the sanctioned account for legal/anti-evasion record-keeping,
 * see SanctionIdentity.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sanction extends Model
{
    use HasFactory;

    public const TYPE_WARNING = 'warning';

    public const TYPE_MUTE = 'mute';

    public const TYPE_SUSPENSION = 'suspension';

    public const TYPE_BAN = 'ban';

    protected $fillable = [
        'user_id',
        'team_id',
        'issued_by',
        'type',
        'reason',
        'starts_at',
        'ends_at',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function identities(): HasMany
    {
        return $this->hasMany(SanctionIdentity::class);
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
