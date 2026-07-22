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
        Schema::create('phase_qualifications', function (Blueprint $table) {
            $table->id();

            // Rank-range sourcing (swiss/round_robin phases): rank_from/rank_to.
            $table->foreignId('source_phase_id')->constrained('tournament_phases')->onDelete('cascade');
            $table->unsignedInteger('rank_from')->nullable();
            $table->unsignedInteger('rank_to')->nullable();

            // Match-outcome sourcing (bracket phases): source_match_id + outcome.
            $table->foreignId('source_match_id')->nullable()->constrained('matches')->onDelete('cascade');
            $table->enum('outcome', ['winner', 'loser'])->nullable();

            // Destination: another phase (possibly in a different tournament) or a final placement.
            $table->enum('destination_type', ['phase', 'placement']);
            $table->foreignId('destination_phase_id')->nullable()->constrained('tournament_phases')->onDelete('cascade');
            $table->unsignedInteger('placement')->nullable();
            $table->string('placement_label')->nullable();

            // Reward earned by reaching this rank/outcome, regardless of destination type.
            // Multi-currency: store the amount and its ISO 4217 code separately rather than
            // a single formatted string, so different rules/tournaments can use different currencies.
            $table->unsignedInteger('points')->nullable();
            $table->decimal('cash_prize_amount', 12, 2)->nullable();
            $table->string('cash_prize_currency', 3)->nullable();

            $table->timestamps();

            $table->index('source_phase_id');
            $table->index('source_match_id');
            $table->index('destination_phase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_qualifications');
    }
};
