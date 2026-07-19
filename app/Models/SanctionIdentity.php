<?php

/**
 * GC-Stats — SanctionIdentity model
 *
 * A fingerprint (email or social provider id) attached to a sanction so the
 * sanction "sticks" to every login method the sanctioned user has ever used,
 * even across account deletion or new providers linked afterwards. Used to
 * detect ban evasion at registration / provider-linking time.
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

class SanctionIdentity extends Model
{
    use HasFactory;

    public const TYPE_EMAIL = 'email';

    protected $fillable = [
        'sanction_id',
        'type',
        'value',
    ];

    public function sanction(): BelongsTo
    {
        return $this->belongsTo(Sanction::class);
    }
}
