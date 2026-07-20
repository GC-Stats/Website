<?php

/**
 * GC-Stats — Add display order to About Us sections
 *
 * Sections previously rendered in whatever order the DB happened to return
 * them in (insertion order). Adds an explicit `order` column, editable from
 * the admin panel, respected by both the admin listing and the public About
 * page.
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
        Schema::table('about_sections', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('key');
        });
    }

    public function down(): void
    {
        Schema::table('about_sections', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
