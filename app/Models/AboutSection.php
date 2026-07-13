<?php

/**
 * GC-Stats — About section model
 *
 * Represents a translatable content block of the "About Us" page
 * (e.g. project presentation, future plans), identified by a unique key.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutSection extends Model
{
    protected $fillable = [
        'key',
        'title',
        'content',
    ];

    protected $casts = [
        'title' => 'array',
        'content' => 'array',
    ];
}
