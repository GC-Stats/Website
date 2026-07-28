<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_map_round_alive_states', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');

            $table->foreignId('game_map_round_id')
                ->constrained('game_map_rounds')
                ->onDelete('cascade');

            $table->unsignedTinyInteger('sequence');
            $table->unsignedInteger('time_ms');
            $table->unsignedTinyInteger('atk_alive');
            $table->unsignedTinyInteger('def_alive');
            $table->enum('winner_side', ['atk', 'def']);

            $table->timestamps();

            $table->index('game_map_round_id');
            $table->index(['atk_alive', 'def_alive', 'winner_side'], 'alive_states_situation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_map_round_alive_states');
    }
};
