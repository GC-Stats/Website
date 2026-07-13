<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_map_round_player_stats', function (Blueprint $table) {
            $table->integer('loadout_value')->default(0)->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('game_map_round_player_stats', function (Blueprint $table) {
            $table->dropColumn('loadout_value');
        });
    }
};
