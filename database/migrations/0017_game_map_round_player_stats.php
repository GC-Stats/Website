<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_map_round_player_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');

            $table->foreignId('game_map_round_id')
                ->constrained('game_map_rounds')
                ->onDelete('cascade');

            $table->foreignId('player_id')
                ->constrained('players')
                ->onDelete('cascade');

            $table->integer('kills')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('score')->default(0);

            $table->integer('economy_spent')->default(0);
            $table->integer('economy_remaining')->default(0);
            $table->string('weapon_id')->nullable();
            $table->string('armor')->nullable();

            $table->timestamps();

            $table->index(['game_map_round_id', 'player_id'], 'round_player_idx');
            $table->index('weapon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_map_round_player_stats');
    }
};
