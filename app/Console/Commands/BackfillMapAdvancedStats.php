<?php

/**
 * GC-Stats — Backfill map advanced stats
 *
 * Detects game maps that were already imported (have a linked Riot match)
 * but are missing the newer per-round detail (kills, damages, ATK/DEF
 * advanced stats) and re-fetches them via Admin\GameMapController::fetchMapData().
 *
 * Historical maps were imported before val_id/team-side resolution existed,
 * so a plain re-fetch can fail with a 422 (ambiguous team side, or a player
 * missing val_id). Where possible this command resolves those cases itself
 * from data already on file — the map's own game_player_stats.team_id
 * (recorded at import time) cross-referenced against Player.val_id — instead
 * of failing the whole map.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Http\Controllers\Admin\GameMapController;
use App\Models\GameMap;
use App\Models\GamePlayerStat;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackfillMapAdvancedStats extends Command
{
    protected $signature = 'maps:backfill-stats
        {--tournament= : Only process maps for this tournament ID}
        {--limit= : Max number of maps to process}
        {--sleep=1.5 : Seconds to wait between Riot API calls}
        {--dry-run : List the maps that would be processed without fetching}';

    protected $description = 'Re-fetch rounds/kills/damages/advanced stats for existing maps that are missing them';

    public function handle(GameMapController $controller): int
    {
        $query = GameMap::query()
            ->whereNotNull('api_match_id')
            ->whereDoesntHave('advancedStats')
            ->with('match.teamA', 'match.teamB');

        if ($tournamentId = $this->option('tournament')) {
            $query->where('tournament_id', (int) $tournamentId);
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $maps = $query->get();
        $this->info("Found {$maps->count()} map(s) missing advanced stats.");

        if ($maps->isEmpty()) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(
                ['Map ID', 'Match ID', 'Tournament ID', 'Map name'],
                $maps->map(fn (GameMap $m) => [$m->id, $m->match_id, $m->tournament_id, $m->map_name])
            );

            return self::SUCCESS;
        }

        $sleep = (float) $this->option('sleep');
        $success = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($maps as $gameMap) {
            $this->line("Map #{$gameMap->id} ({$gameMap->map_name}, match #{$gameMap->match_id})");

            [$status, $body] = $this->attemptFetch($controller, $gameMap);

            if ($status === 422 && isset($body['available_colors'])) {
                $teamAColor = $this->resolveTeamAColorFromDb($gameMap, $body['players'] ?? []);

                if ($teamAColor) {
                    $this->comment("  Ambiguous team side — resolved '{$teamAColor}' from existing game_player_stats, retrying");
                    usleep((int) ($sleep * 1_000_000));
                    [$status, $body] = $this->attemptFetch($controller, $gameMap, ['team_a_color' => $teamAColor]);
                }
            }

            if ($status === 422 && isset($body['missing_val_ids'])) {
                $puuidMapping = $this->resolvePuuidMappingFromDb($gameMap, $body['missing_val_ids']);

                if (! empty($puuidMapping)) {
                    $this->comment('  Resolved '.count($puuidMapping).' missing val_id(s) from existing data, retrying');
                    usleep((int) ($sleep * 1_000_000));
                    [$status, $body] = $this->attemptFetch($controller, $gameMap, ['puuid_mapping' => $puuidMapping]);
                }
            }

            if ($status < 300) {
                $this->info('  OK');
                $success++;
            } elseif ($status === 422) {
                $reason = $body['error'] ?? 'unresolved 422';
                $this->warn("  Skipped: {$reason}");
                $skipped++;
            } else {
                $this->error('  Failed (HTTP '.$status.'): '.($body['error'] ?? 'unknown error'));
                $failed++;
            }

            usleep((int) ($sleep * 1_000_000));
        }

        $this->newLine();
        $this->info("Done. Success: {$success}, Skipped: {$skipped}, Failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: array|null}
     */
    private function attemptFetch(GameMapController $controller, GameMap $gameMap, array $params = []): array
    {
        $request = Request::create('/', 'GET', $params);
        $response = $controller->fetchMapData($gameMap->id, $request);

        $body = $response instanceof JsonResponse ? $response->getData(true) : null;

        return [$response->getStatusCode(), $body];
    }

    /**
     * Resolve which raw Riot team color is "Team A" using the map's own
     * historical game_player_stats.team_id, cross-referenced against the
     * unresolved players' puuid via Player.val_id — avoids failing on maps
     * whose current roster no longer matches who actually played.
     */
    private function resolveTeamAColorFromDb(GameMap $gameMap, array $players): ?string
    {
        $teamAId = $gameMap->match->team_a_id;

        $puuids = collect($players)->pluck('puuid')->filter()->values();
        $playerIdByPuuid = Player::whereIn('val_id', $puuids)->pluck('id', 'val_id');

        if ($playerIdByPuuid->isEmpty()) {
            return null;
        }

        $teamIdByPlayerId = GamePlayerStat::where('game_map_id', $gameMap->id)
            ->whereIn('player_id', $playerIdByPuuid->values())
            ->pluck('team_id', 'player_id');

        foreach ($players as $player) {
            $playerId = $playerIdByPuuid[$player['puuid']] ?? null;
            $teamId = $playerId ? ($teamIdByPlayerId[$playerId] ?? null) : null;

            if ($teamId && (int) $teamId === (int) $teamAId) {
                return $player['team'];
            }
        }

        return null;
    }

    /**
     * Resolve puuid => player_id for players the controller couldn't match
     * to a val_id, using the map's own historical game_player_stats: match
     * the unresolved player by team + agent against that map's recorded
     * lineup, since names/handles may have changed since import.
     */
    private function resolvePuuidMappingFromDb(GameMap $gameMap, array $missingValIds): array
    {
        $lineup = GamePlayerStat::where('game_map_id', $gameMap->id)
            ->whereNotNull('player_id')
            ->get(['player_id', 'team_id', 'agent_name']);

        $teamAId = $gameMap->match->team_a_id;
        $teamBId = $gameMap->match->team_b_id;

        $mapping = [];

        foreach ($missingValIds as $missing) {
            $teamId = match (true) {
                $missing['team'] === $gameMap->match->teamA?->short_name => $teamAId,
                $missing['team'] === $gameMap->match->teamB?->short_name => $teamBId,
                default => null,
            };

            if (! $teamId) {
                continue;
            }

            $candidate = $lineup->first(fn ($row) => (int) $row->team_id === (int) $teamId
                && strcasecmp($row->agent_name, (string) $missing['agent']) === 0);

            if ($candidate) {
                $mapping[$missing['puuid']] = $candidate->player_id;
            }
        }

        return $mapping;
    }
}
