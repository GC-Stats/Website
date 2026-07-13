<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    public $timestamps = false;

    protected $table = 'api_request_log';

    protected $fillable = [
        'api_key_id',
        'method',
        'endpoint',
        'status_code',
        'duration_ms',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
