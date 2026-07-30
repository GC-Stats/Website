<?php

/**
 * GC-Stats — Backfill map advanced stats
 *
 * Detects game maps that were already imported (have a linked Riot match)
 * but are missing the newer per-round detail (kills, damages, ATK/DEF
 * advanced stats, XvY alive-state timeline, kill/plant/defuse positions)
 * and re-fetches them via Admin\GameMapController::fetchMapData(). Selection
 * is keyed on the alive-state timeline rather than advancedStats, since a
 * map fetched before that table existed will have advancedStats but nothing
 * in game_map_round_alive_states/game_map_round_player_positions — a plain
 * re-fetch backfills all of it in one pass regardless of which piece was
 * missing.
 *
 * Historical maps were imported before val_id/team-side resolution existed,
 * or the API's official match endpoint has since expired its cache and only
 * resolves via the esports endpoint (a different puuid space, with its own
 * esports_val_id identity column never previously populated) — so a plain
 * re-fetch can fail with a 422 (ambiguous team side, or a player missing
 * val_id/esports_val_id for the endpoint in use). Where possible this
 * command resolves those cases itself from data already on file: primarily
 * by matching the map's own game_player_stats.val_name (the exact
 * "gameName#tagLine" recorded at a prior successful import) against the
 * missing player's name from the new fetch, falling back to team_id +
 * agent_name matching for older maps that predate val_name — instead of
 * failing the whole map.
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

    protected $description = 'Re-fetch rounds/kills/damages/advanced stats/alive-states/positions for existing maps that are missing them';

    public function handle(GameMapController $controller): int
    {
        $query = GameMap::query()
            ->whereNotNull('api_match_id')
            ->where(function ($q) {
                $q->whereDoesntHave('advancedStats')
                    ->orWhereDoesntHave('rounds.aliveStates');
            })
            ->with('match.teamA', 'match.teamB')
            ->join('matches', 'matches.id', '=', 'game_maps.match_id')
            ->orderByDesc('matches.scheduled_at')
            ->select('game_maps.*');

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
        $failuresByType = [];

        foreach ($maps as $gameMap) {
            $this->line("Map #{$gameMap->id} ({$gameMap->map_name}, match #{$gameMap->match_id})");

            try {
                [$status, $body] = $this->attemptFetch($controller, $gameMap);

                $params = [];

                for ($attempt = 0; $attempt < 5; $attempt++) {
                    if ($status !== 422) {
                        break;
                    }

                    if (isset($body['missing_val_ids'])) {
                        $puuidMapping = $this->resolvePuuidMappingFromDb($gameMap, $body['missing_val_ids']);

                        if (empty($puuidMapping)) {
                            break;
                        }

                        $this->comment('  Resolved '.count($puuidMapping).' missing val_id(s) from existing data, retrying');
                        $params['puuid_mapping'] = $puuidMapping;
                    } elseif (isset($body['available_colors'])) {
                        $teamAColor = $this->resolveTeamAColorFromDb($gameMap, $body['players'] ?? []);

                        if (! $teamAColor) {
                            break;
                        }

                        $this->comment("  Ambiguous team side — resolved '{$teamAColor}' from existing game_player_stats, retrying");
                        $params['team_a_color'] = $teamAColor;
                    } else {
                        break;
                    }

                    usleep((int) ($sleep * 1_000_000));
                    [$status, $body] = $this->attemptFetch($controller, $gameMap, $params);
                }
            } catch (\Throwable $e) {
                $this->error('  PHP error: '.$e->getMessage());
                $failed++;
                $failuresByType[get_class($e).': '.$e->getMessage()] = ($failuresByType[get_class($e).': '.$e->getMessage()] ?? 0) + 1;
                usleep((int) ($sleep * 1_000_000));

                continue;
            }

            if ($status < 300) {
                $this->info('  OK');
                $success++;
            } elseif ($status === 422) {
                $reason = $body['error'] ?? 'unresolved 422';
                $this->warn("  Skipped: {$reason}");
                $skipped++;
            } else {
                $reason = $body['error'] ?? 'unknown error';
                $this->error('  Failed (HTTP '.$status.'): '.$reason);
                $failed++;

                $type = match (true) {
                    $status === 404 => "API 404: {$reason}",
                    $status >= 500 => "API {$status} (server error): {$reason}",
                    default => "API {$status}: {$reason}",
                };
                $failuresByType[$type] = ($failuresByType[$type] ?? 0) + 1;
            }

            usleep((int) ($sleep * 1_000_000));
        }

        $this->newLine();
        $this->info("Done. Success: {$success}, Skipped: {$skipped}, Failed: {$failed}");

        if (! empty($failuresByType)) {
            $this->newLine();
            $this->warn('Failures by type:');
            $this->table(
                ['Type', 'Count'],
                collect($failuresByType)
                    ->sortDesc()
                    ->map(fn ($count, $type) => [$type, $count])
                    ->values()
            );
        }

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
     * to a val_id/esports_val_id, using the map's own historical
     * game_player_stats — most commonly needed when the official match
     * endpoint's cache has expired and the esports endpoint (a distinct
     * puuid space, with its own never-before-populated esports_val_id
     * column) is the only way left to re-fetch this map.
     *
     * Primary strategy: exact match on val_name, the "gameName#tagLine"
     * string recorded verbatim at a prior successful import — a precise
     * identity match that doesn't depend on knowing which raw team color is
     * "Team A" for this fetch attempt. Falls back to team_id + agent_name
     * matching (less precise — breaks down if a player agent-swapped
     * between visits to this map) for older maps imported before val_name
     * was recorded.
     */
    private function resolvePuuidMappingFromDb(GameMap $gameMap, array $missingValIds): array
    {
        $lineup = GamePlayerStat::where('game_map_id', $gameMap->id)
            ->whereNotNull('player_id')
            ->get(['player_id', 'team_id', 'agent_name', 'val_name']);

        $byValName = $lineup->whereNotNull('val_name')->pluck('player_id', 'val_name');

        $teamAId = $gameMap->match->team_a_id;
        $teamBId = $gameMap->match->team_b_id;

        $mapping = [];

        foreach ($missingValIds as $missing) {
            if (isset($byValName[$missing['name']])) {
                $mapping[$missing['puuid']] = $byValName[$missing['name']];

                continue;
            }

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
