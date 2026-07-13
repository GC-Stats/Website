<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_map_round_damages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');

            $table->foreignId('game_map_round_id')
                ->constrained('game_map_rounds')
                ->onDelete('cascade');

            $table->foreignId('attacker_player_id')
                ->nullable()
                ->constrained('players')
                ->onDelete('set null');

            $table->foreignId('receiver_player_id')
                ->constrained('players')
                ->onDelete('cascade');

            $table->integer('damage')->default(0);
            $table->integer('headshots')->default(0);
            $table->integer('bodyshots')->default(0);
            $table->integer('legshots')->default(0);

            $table->timestamps();

            $table->index(['game_map_round_id', 'attacker_player_id'], 'round_damage_attacker_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_map_round_damages');
    }
};
