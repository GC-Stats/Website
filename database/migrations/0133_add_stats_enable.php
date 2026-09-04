<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This is a shit / temp fixes for manual entered stats, a better solution will be made for V2
        Schema::table('game_player_stats', function (Blueprint $table) {
            $table->boolean('stats_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_player_povs');
    }
};
