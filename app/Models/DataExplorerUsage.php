<?php

/**
 * GC-Stats — Data Explorer monthly usage counter
 *
 * One row per user per calendar month, tracking how many natural-language
 * queries were served from the platform's shared key vs. the user's own
 * BYOK key. See DataExplorerQuotaService::claimRequestSlot().
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

class DataExplorerUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'platform_requests_count',
        'personal_requests_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalRequestsCount(): int
    {
        return $this->platform_requests_count + $this->personal_requests_count;
    }
}
