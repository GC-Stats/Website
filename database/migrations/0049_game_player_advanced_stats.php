<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_player_advanced_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('phase_id')->constrained('tournament_phases');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_map_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null');
            $table->string('agent_name');

            for ($n = 1; $n <= 5; $n++) {
                $table->integer("clutch_1v{$n}_won")->default(0);
                $table->integer("clutch_1v{$n}_total")->default(0);
            }

            $table->integer('multikill_2k')->default(0);
            $table->integer('multikill_3k')->default(0);
            $table->integer('multikill_4k')->default(0);
            $table->integer('multikill_5k')->default(0);

            $table->integer('trade_kills')->default(0);
            $table->integer('traded_deaths')->default(0);

            $table->integer('plants')->default(0);
            $table->integer('defuses')->default(0);

            $table->integer('pistol_won')->default(0);
            $table->integer('pistol_played')->default(0);
            $table->integer('eco_won')->default(0);
            $table->integer('eco_played')->default(0);
            $table->integer('force_won')->default(0);
            $table->integer('force_played')->default(0);
            $table->integer('full_buy_won')->default(0);
            $table->integer('full_buy_played')->default(0);
            $table->integer('post_plant_won')->default(0);
            $table->integer('post_plant_played')->default(0);

            $table->integer('atk_rounds')->default(0);
            $table->integer('atk_rounds_won')->default(0);
            $table->integer('atk_kills')->default(0);
            $table->decimal('atk_kast_percentage', 5, 2)->default(0);
            $table->integer('def_rounds')->default(0);
            $table->integer('def_rounds_won')->default(0);
            $table->integer('def_kills')->default(0);
            $table->decimal('def_kast_percentage', 5, 2)->default(0);

            $table->timestamps();

            $table->index(['game_map_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_player_advanced_stats');
    }
};
