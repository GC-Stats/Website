<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_map_round_player_positions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');

            $table->foreignId('game_map_round_id')
                ->constrained('game_map_rounds')
                ->onDelete('cascade');

            $table->enum('event_type', ['kill', 'plant', 'defuse']);

            $table->foreignId('game_map_round_kill_id')
                ->nullable()
                ->constrained('game_map_round_kills')
                ->onDelete('set null');

            $table->foreignId('player_id')->constrained()->onDelete('cascade');

            $table->enum('role', ['killer', 'victim', 'bystander', 'planter', 'defuser'])->nullable();

            $table->integer('x');
            $table->integer('y');
            $table->float('view_radians')->nullable();
            $table->unsignedInteger('time_ms')->nullable();

            $table->timestamps();

            $table->index('game_map_round_id');
            $table->index('game_map_round_kill_id');
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_map_round_player_positions');
    }
};
