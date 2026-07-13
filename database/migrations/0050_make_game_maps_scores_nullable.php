<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->integer('team_a_score')->nullable()->default(null)->change();
            $table->integer('team_b_score')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->integer('team_a_score')->default(0)->change();
            $table->integer('team_b_score')->default(0)->change();
        });
    }
};
