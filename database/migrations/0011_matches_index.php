<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->index('status');

            $table->index('scheduled_at');

            $table->index(['tournament_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_status_index');
            $table->dropIndex('matches_scheduled_at_index');
        });
        Schema::table('matches', function (Blueprint $table) {
            $table->index('tournament_id', 'matches_tournament_id_foreign');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_tournament_id_scheduled_at_index');
        });
    }
};
