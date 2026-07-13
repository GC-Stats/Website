<?php

/**
 * GC-Stats — Finance entry model
 *
 * Represents a single income or expense entry of the public finance
 * ledger displayed on the Finance transparency page.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceEntry extends Model
{
    protected $fillable = [
        'entry_date',
        'type',
        'category',
        'label',
        'description',
        'amount_usd',
        'amount_eur',
        'source_url',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount_usd' => 'decimal:2',
        'amount_eur' => 'decimal:2',
    ];
}
