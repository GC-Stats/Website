<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores a locale-agnostic code (see App\Support\Pronouns) rather than
     * a French word, since every lang/{locale}/*.php file needs to render
     * its own gendered wording (or none, for locales without grammatical
     * gender) from the same value.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('pronouns')->default(2)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pronouns');
        });
    }
};
