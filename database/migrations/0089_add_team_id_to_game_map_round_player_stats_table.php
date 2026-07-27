<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_map_round_player_stats', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('player_id')
                ->constrained()->onDelete('set null');
        });

        // Backfill from game_player_stats, which already tracks the team a
        // player was on for a given map (game_map_id + player_id is unique
        // per player per map there).
        DB::statement(<<<'SQL'
            UPDATE game_map_round_player_stats AS round_stats
            INNER JOIN game_map_rounds AS rounds ON rounds.id = round_stats.game_map_round_id
            INNER JOIN game_player_stats AS map_stats
                ON map_stats.game_map_id = rounds.game_map_id
                AND map_stats.player_id = round_stats.player_id
            SET round_stats.team_id = map_stats.team_id
            WHERE round_stats.team_id IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('game_map_round_player_stats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
