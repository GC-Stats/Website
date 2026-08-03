<?php

/**
 * GC-Stats — Player position heatmap service
 *
 * Pulls filtered rows from game_map_round_player_positions (snapshots taken
 * at a kill, plant, or defuse — see App\Models\GameMapRoundPlayerPosition)
 * and converts Riot's raw in-game x/y into a normalized 0-1 position on the
 * map's tactical minimap, using the calibration coefficients in
 * config/valorant_minimaps.php. Backs the "Positions Heatmap" broadcast
 * widget (App\Http\Controllers\Public\WidgetController::heatmap()).
 *
 * The raw-to-display axes are cross-wired: valorant-api.com's "x"
 * coefficients (xMultiplier/xScalarToAdd) produce the display X from the
 * *raw Y* coordinate, and the "y" coefficients produce display Y from the
 * *raw X* coordinate — not axis-matched as the field names suggest. This
 * was verified empirically against game_map_rounds.plant_x/plant_y for
 * known bomb sites (Ascent A/B) against the downloaded tactical minimap.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HeatmapService
{
    /**
     * $mapName is expected lowercase (a config/valorant_minimaps.php key);
     * matched case-insensitively against game_maps.map_name, which is
     * stored in Riot's display casing (e.g. "Ascent").
     *
     * @param  list<string>|null  $eventTypes  kill/plant/defuse, null = all
     * @param  string|null  $agent  agent_name from config('valorant.agents'), matched against game_player_stats
     *                              (one row per player per map — not tracked per round, so an agent swap mid-match
     *                              isn't distinguishable; filters to whatever agent that player used on that map)
     * @param  int|null  $timeStart  lower bound in seconds, relative to $timeReference
     * @param  int|null  $timeEnd  upper bound in seconds, relative to $timeReference
     * @param  string  $timeReference  'round' (default) measures from round start against p.time_ms directly;
     *                                 'plant' measures from that round's plant instead, excluding rounds that
     *                                 were never planted — useful for isolating post-plant positioning regardless
     *                                 of how long the round ran before the plant happened
     * @return list<array{x: float, y: float, side: ?string, event_type: string, team_id: ?int, player_id: int}>
     */
    public function positions(
        string $mapName,
        ?int $tournamentId = null,
        ?Carbon $start = null,
        ?Carbon $end = null,
        ?string $side = null,
        ?int $teamId = null,
        ?int $playerId = null,
        ?array $eventTypes = null,
        ?string $agent = null,
        ?int $timeStart = null,
        ?int $timeEnd = null,
        string $timeReference = 'round',
    ): array {
        $calibration = config('valorant_minimaps.'.$mapName);

        if (! $calibration) {
            return [];
        }

        $gameMapIds = DB::table('game_maps')->whereRaw('LOWER(map_name) = ?', [$mapName])->pluck('id')->all();

        if (empty($gameMapIds)) {
            return [];
        }

        $fromPlant = $timeReference === 'plant';

        $rows = DB::table('game_map_round_player_positions as p')
            ->join('game_map_rounds as r', 'r.id', '=', 'p.game_map_round_id')
            ->join('matches as m', 'm.id', '=', 'p.match_id')
            ->leftJoin('game_map_round_player_stats as s', function ($join) {
                $join->on('s.game_map_round_id', '=', 'p.game_map_round_id')
                    ->on('s.player_id', '=', 'p.player_id');
            })
            ->leftJoin('game_player_stats as gps', function ($join) {
                $join->on('gps.game_map_id', '=', 'p.game_map_id')
                    ->on('gps.player_id', '=', 'p.player_id');
            })
            ->whereIn('p.game_map_id', $gameMapIds)
            ->when($fromPlant, fn ($q) => $q->whereNotNull('r.plant_time_ms'))
            ->when($tournamentId, fn ($q) => $q->where('p.tournament_id', $tournamentId))
            ->when($start && $end, fn ($q) => $q->whereBetween('m.scheduled_at', [$start, $end]))
            ->when($teamId, fn ($q) => $q->where('s.team_id', $teamId))
            ->when($playerId, fn ($q) => $q->where('p.player_id', $playerId))
            ->when($eventTypes, fn ($q) => $q->whereIn('p.event_type', $eventTypes))
            ->when($agent, fn ($q) => $q->where('gps.agent_name', $agent))
            ->when($timeStart !== null, fn ($q) => $fromPlant
                ? $q->whereRaw('CAST(p.time_ms AS SIGNED) - CAST(r.plant_time_ms AS SIGNED) >= ?', [$timeStart * 1000])
                : $q->where('p.time_ms', '>=', $timeStart * 1000))
            ->when($timeEnd !== null, fn ($q) => $fromPlant
                ? $q->whereRaw('CAST(p.time_ms AS SIGNED) - CAST(r.plant_time_ms AS SIGNED) <= ?', [$timeEnd * 1000])
                : $q->where('p.time_ms', '<=', $timeEnd * 1000))
            ->when($side === 'atk', fn ($q) => $q->whereColumn('s.team_id', 'r.atk_team'))
            ->when($side === 'def', fn ($q) => $q->whereColumn('s.team_id', 'r.def_team'))
            ->select('p.x', 'p.y', 'p.event_type', 'p.player_id', 's.team_id', 'r.atk_team', 'r.def_team')
            ->orderByDesc('p.id')
            ->limit(5000)
            ->get();

        return $rows->map(function ($row) use ($calibration) {
            $xMap = $calibration['x_multiplier'] * $row->y + $calibration['x_scalar'];
            $yMap = $calibration['y_multiplier'] * $row->x + $calibration['y_scalar'];

            $rowSide = null;
            if ($row->team_id !== null) {
                $rowSide = $row->team_id == $row->atk_team ? 'atk' : ($row->team_id == $row->def_team ? 'def' : null);
            }

            return [
                'x' => $xMap,
                'y' => $yMap,
                'side' => $rowSide,
                'event_type' => $row->event_type,
                'team_id' => $row->team_id,
                'player_id' => $row->player_id,
            ];
        })->all();
    }
}
