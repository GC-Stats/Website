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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained();

            $table->foreignId('phase_id')->constrained('tournament_phases');

            $table->integer('round_number')->default(1);
            $table->string('round_name')->default('');
            $table->integer('match_order')->default(1);

            $table->foreignId('team_a_id')->nullable();
            $table->foreignId('team_b_id')->nullable();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['upcoming', 'live', 'finished'])->default('upcoming');
            $table->integer('team_a_score')->default(0);
            $table->integer('team_b_score')->default(0);
            $table->integer('best_of')->default(1);

            $table->string('patch')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
