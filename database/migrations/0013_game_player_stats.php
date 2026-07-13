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
        Schema::create('game_player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
            $table->foreignId('game_map_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('team_id')->constrained();
            $table->string('agent_name');

            $table->integer('kills')->default(0);
            $table->integer('deaths')->default(0);
            $table->integer('assists')->default(0);

            $table->integer('acs')->default(0);
            $table->integer('adr')->default(0);
            $table->decimal('kast_percentage', 5, 2)->default(0);

            $table->integer('first_kills')->default(0);
            $table->integer('first_deaths')->default(0);
            $table->decimal('headshot_percentage', 5, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_player_stats');
    }
};
