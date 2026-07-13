<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->index(['team_a_id', 'scheduled_at'], 'idx_matches_team_a_scheduled');
            $table->index(['team_b_id', 'scheduled_at'], 'idx_matches_team_b_scheduled');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('idx_matches_team_a_scheduled');
            $table->dropIndex('idx_matches_team_b_scheduled');
        });
    }
};
