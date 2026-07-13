<?php

/**
 * GC-Stats — About team member model
 *
 * Represents a member of the GC-Stats team displayed on the "About Us"
 * page (name, role, bio, photo and social network links).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutTeamMember extends Model
{
    protected $fillable = [
        'name',
        'role',
        'bio',
        'photo_url',
        'socials',
        'order',
        'is_active',
    ];

    protected $casts = [
        'role' => 'array',
        'bio' => 'array',
        'socials' => 'array',
        'is_active' => 'boolean',
    ];
}
