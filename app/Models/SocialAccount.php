<?php

/**
 * GC-Stats — SocialAccount model
 *
 * Represents a Socialite login provider (Twitter/X, Twitch, Discord) linked
 * to a user account. A user may link several providers to the same account.
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

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'nickname',
        'avatar',
        'token',
        'refresh_token',
        'token_expires_at',
        'provider_created_at',
    ];

    protected $hidden = [
        'token',
        'refresh_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'provider_created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
