<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_map_round_kills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');

            $table->foreignId('game_map_round_id')
                ->constrained('game_map_rounds')
                ->onDelete('cascade');

            $table->foreignId('killer_player_id')
                ->nullable()
                ->constrained('players')
                ->onDelete('set null');

            $table->foreignId('victim_player_id')
                ->constrained('players')
                ->onDelete('cascade');

            $table->integer('time_ms');
            $table->string('weapon')->nullable();
            $table->string('damage_type')->nullable();
            $table->boolean('is_secondary_fire')->default(false);
            $table->json('assistant_player_ids')->nullable();

            $table->timestamps();

            $table->index('game_map_round_id');
            $table->index('killer_player_id');
            $table->index('victim_player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_map_round_kills');
    }
};
