<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->index('handle', 'idx_players_handle');

            $table->index(['is_active', 'handle'], 'idx_players_active_listing');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->index('name', 'idx_teams_name');
            $table->index('is_active', 'idx_teams_active');
        });

        Schema::table('player_team', function (Blueprint $table) {
            $table->index(['player_id', 'left_at'], 'idx_pt_current_player_team');

            $table->index(['team_id', 'left_at'], 'idx_pt_team_roster');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('idx_players_handle');
            $table->dropIndex('idx_players_active_listing');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('idx_teams_name');
            $table->dropIndex('idx_teams_active');
        });

        Schema::table('player_team', function (Blueprint $table) {
            $table->index('player_id', 'player_team_player_id_foreign');
            $table->index('team_id', 'player_team_team_id_foreign');
        });

        Schema::table('player_team', function (Blueprint $table) {
            $table->dropIndex('idx_pt_current_player_team');
            $table->dropIndex('idx_pt_team_roster');
        });
    }
};
