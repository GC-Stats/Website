<?php

/**
 * GC-Stats — Add start/end dates to tournament phases
 *
 * Nullable, editable only for top-level (non-child) phases — a nested phase
 * inherits its scheduling window from its parent conceptually, so it has no
 * dates of its own.
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
        Schema::table('tournament_phases', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('format');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_phases', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
