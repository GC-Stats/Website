<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $table = 'api_key';

    protected $fillable = [
        'client_name',
        'key_hash',
        'rate_limit',
        'is_active',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Source of truth for the auth hash contract shared with the external
     * Rust API, which looks keys up by this exact same hash('sha256', ...)
     * — changing this breaks auth for every key unless mirrored there too.
     * Exception: database/migrations/0051_hash_api_key_values.php backfills
     * historical rows via raw SQL (SHA2()) and intentionally does not call
     * this method — migrations must stay pinned to what actually ran.
     */
    public static function hashKey(string $clearKey): string
    {
        return hash('sha256', $clearKey);
    }
}
