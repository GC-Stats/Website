<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('liquipedia_link')->nullable()->after('vlr_id');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->string('liquipedia_link')->nullable()->after('vlr_id');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('liquipedia_link')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('liquipedia_link');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('liquipedia_link');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('liquipedia_link');
        });
    }
};
