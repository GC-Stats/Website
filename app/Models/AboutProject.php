<?php

/**
 * GC-Stats — About project model
 *
 * Represents a project of the GC-Stats organisation displayed on the
 * "About Us" page (name, description, link and logo).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutProject extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'url',
        'logo_url',
        'order',
        'is_active',
    ];

    protected $casts = [
        'description' => 'array',
        'is_active' => 'boolean',
    ];
}
