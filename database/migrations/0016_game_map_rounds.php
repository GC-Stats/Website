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
        Schema::create('game_map_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_map_id')->constrained()->onDelete('cascade');
            $table->integer('round_number');
            $table->foreignId('winning_team')->constrained(table: 'teams', column: 'id')->onDelete('cascade');
            $table->string('win_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_map_rounds');
    }
};
