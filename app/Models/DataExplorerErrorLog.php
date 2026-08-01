<?php

/**
 * GC-Stats — Data Explorer error log
 *
 * Persists every failed AI-query/query-builder request so a user's error
 * reference ID can actually be looked up (payload, upstream error code and
 * raw message, HTTP status). request_id is the same UUID sent to
 * GC-Stats-API and logged there too. Pruned after 30 days, see
 * App\Console\Commands\ScheduledCommand\PruneDataExplorerErrorLogs.
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

class DataExplorerErrorLog extends Model
{
    use HasFactory;

    public const SOURCE_QUERY = 'query';

    public const SOURCE_BUILDER = 'builder';

    protected $fillable = [
        'request_id',
        'user_id',
        'source',
        'request_payload',
        'error_code',
        'error_message',
        'http_status',
    ];

    protected $casts = [
        'request_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
