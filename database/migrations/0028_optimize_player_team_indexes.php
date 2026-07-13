<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_team', function (Blueprint $table) {
            $table->index(['player_id', 'left_at', 'joined_at'], 'idx_pt_player_left_joined');
            $table->index(['team_id', 'left_at', 'joined_at'], 'idx_pt_team_left_joined');
        });

        Schema::table('player_team', function (Blueprint $table) {
            $table->dropIndex('idx_pt_current_player_team');
            $table->dropIndex('idx_pt_team_roster');
        });
    }

    public function down(): void
    {
        Schema::table('player_team', function (Blueprint $table) {
            $table->index(['player_id', 'left_at'], 'idx_pt_current_player_team');
            $table->index(['team_id', 'left_at'], 'idx_pt_team_roster');
        });

        Schema::table('player_team', function (Blueprint $table) {
            $table->dropIndex('idx_pt_player_left_joined');
            $table->dropIndex('idx_pt_team_left_joined');
        });
    }
};
