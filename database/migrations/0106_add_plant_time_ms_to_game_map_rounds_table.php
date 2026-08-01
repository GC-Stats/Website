<?php

/**
 * GC-Stats — Store the round-clock plant time directly on game_map_rounds
 *
 * Sits alongside plant_site/plant_x/plant_y (added in migration 0092).
 * Previously the only record of a round's plant time was buried in its
 * 'plant' game_map_round_player_positions rows (one per player alive at
 * plant time), which meant computing it required aggregating those rows
 * per round. Storing it directly on the round lets the heatmap's
 * plant-relative time filter (HeatmapService::positions($timeReference))
 * join straight against game_map_rounds instead of a derived subquery.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_map_rounds', function (Blueprint $table) {
            $table->unsignedInteger('plant_time_ms')->nullable()->after('plant_y');
        });

        DB::statement(<<<'SQL'
            UPDATE game_map_rounds r
            JOIN (
                SELECT game_map_round_id, MIN(time_ms) AS plant_time_ms
                FROM game_map_round_player_positions
                WHERE event_type = 'plant'
                GROUP BY game_map_round_id
            ) p ON p.game_map_round_id = r.id
            SET r.plant_time_ms = p.plant_time_ms
            WHERE r.plant_time_ms IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('game_map_rounds', function (Blueprint $table) {
            $table->dropColumn('plant_time_ms');
        });
    }
};
