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
        Schema::create('game_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');
            $table->string('api_match_id')->unique()->nullable();
            $table->string('map_name');
            $table->integer('team_a_score')->default(0);
            $table->integer('team_b_score')->default(0);
            $table->integer('order');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_maps');
    }
};
