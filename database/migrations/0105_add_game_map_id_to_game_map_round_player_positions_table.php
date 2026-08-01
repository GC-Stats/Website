<?php

/**
 * GC-Stats — Denormalize game_map_id onto game_map_round_player_positions
 *
 * The heatmap widget (App\Services\HeatmapService::positions()) previously
 * had to join game_map_round_player_positions -> game_map_rounds -> game_maps
 * and filter with whereRaw('LOWER(gm.map_name) = ?', ...) to select rows for
 * a given map, which can't use an index and forces a scan of the joined rows
 * before the map filter is applied. Storing game_map_id directly lets that
 * query filter p.game_map_id with an index instead.
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
        Schema::table('game_map_round_player_positions', function (Blueprint $table) {
            $table->foreignId('game_map_id')
                ->nullable()
                ->after('game_map_round_id')
                ->constrained('game_maps')
                ->onDelete('cascade');
        });

        DB::statement(<<<'SQL'
            UPDATE game_map_round_player_positions p
            JOIN game_map_rounds r ON r.id = p.game_map_round_id
            SET p.game_map_id = r.game_map_id
            WHERE p.game_map_id IS NULL
        SQL);

        Schema::table('game_map_round_player_positions', function (Blueprint $table) {
            $table->index(['game_map_id', 'tournament_id']);
        });
    }

    public function down(): void
    {
        Schema::table('game_map_round_player_positions', function (Blueprint $table) {
            $table->dropIndex(['game_map_id', 'tournament_id']);
            $table->dropConstrainedForeignId('game_map_id');
        });
    }
};
