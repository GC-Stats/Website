<?php

/**
 * GC-Stats — Resolves match context trait
 *
 * Shared logic to auto-fill the tournament_id/phase_id/match_id "context"
 * columns of a raw-data model from its known parent (a match, a game map,
 * or a game map round) at creation time. Extracted from the duplicated
 * booted() hooks previously found on GameMap, GameMapRound,
 * GameMapRoundDamage, GameMapRoundKill, GameMapRoundPlayerStat,
 * GamePlayerAdvancedStat and GamePlayerStat so the same SQL isn't
 * maintained in seven places.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models\Concerns;

use App\Models\GameMap;
use App\Models\GameMapRound;
use App\Models\Matchs;
use Illuminate\Database\Eloquent\Model;

trait ResolvesMatchContext
{
    /**
     * Fill tournament_id/phase_id from the parent match (via match_id),
     * when not already set. Used by models created directly under a match
     * (GameMap, GamePlayerStat, GamePlayerAdvancedStat).
     */
    protected static function resolveContextFromMatch(Model $model): void
    {
        if ((! $model->tournament_id || ! $model->phase_id) && $model->match_id) {
            $match = Matchs::where('id', $model->match_id)
                ->select('tournament_id', 'phase_id')
                ->first();

            if ($match) {
                $model->tournament_id = $model->tournament_id ?: $match->tournament_id;
                $model->phase_id = $model->phase_id ?: $match->phase_id;
            }
        }
    }

    /**
     * Fill match_id/tournament_id/phase_id from the parent game map (via
     * game_map_id), when not already set. Used by GameMapRound.
     */
    protected static function resolveContextFromGameMap(Model $model): void
    {
        if (! $model->game_map_id) {
            return;
        }

        $matchData = GameMap::where('id', $model->game_map_id)
            ->with('match:id,tournament_id,phase_id')
            ->first();

        if ($matchData && $matchData->match) {
            $model->match_id = $model->match_id ?? $matchData->match_id;
            $model->tournament_id = $model->tournament_id ?? $matchData->match->tournament_id;
            $model->phase_id = $model->phase_id ?? $matchData->match->phase_id;
        }
    }

    /**
     * Fill match_id/tournament_id/phase_id from the parent game map round
     * (via game_map_round_id), when not already set. Used by models created
     * directly under a round (GameMapRoundDamage, GameMapRoundKill,
     * GameMapRoundPlayerStat).
     */
    protected static function resolveContextFromRound(Model $model): void
    {
        if (! $model->game_map_round_id) {
            return;
        }

        $roundData = GameMapRound::query()
            ->where('id', $model->game_map_round_id)
            ->with(['gameMap.match' => function ($query) {
                $query->select('id', 'tournament_id', 'phase_id');
            }])
            ->first();

        if ($roundData && $roundData->gameMap && $roundData->gameMap->match) {
            $match = $roundData->gameMap->match;

            $model->match_id = $model->match_id ?? $roundData->gameMap->match_id;
            $model->tournament_id = $model->tournament_id ?? $match->tournament_id;
            $model->phase_id = $model->phase_id ?? $match->phase_id;
        }
    }
}
