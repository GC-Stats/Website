<?php

/**
 * GC-Stats — Finance entries table
 *
 * Stores the public ledger ("livre de comptes") entries shown on the
 * Finance transparency page: incomes and expenses, with a plain text
 * label/description (locale-independent) and an optional proof/source
 * link.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 100);
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('amount_usd', 10, 2);
            $table->decimal('amount_eur', 10, 2);
            $table->string('source_url')->nullable();
            $table->timestamps();

            $table->index('entry_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_entries');
    }
};
