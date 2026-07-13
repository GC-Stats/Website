<?php

/**
 * GC-Stats — Logo history model
 *
 * Represents a historical logo used by a team, player or tournament during
 * a given time range (from/until), allowing the correct logo to be shown
 * for past matches.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Logo extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'entity_type',
        'entity_id',
        'from',
        'until',
    ];

    protected $casts = [
        'from' => 'datetime',
        'until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Logo $logo) {
            // The id is used as the storage directory name, so it must always
            // be a server-generated UUID — never an arbitrary caller-supplied value.
            if (! $logo->id || ! Str::isUuid($logo->id)) {
                $logo->id = (string) Str::uuid();
            }
        });
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
