<?php

/**
 * GC-Stats — Game map API controller
 *
 * Fetches and computes detailed per-map statistics (rounds, trades, player
 * stats) for a given game map by calling the Riot match API and processing
 * the raw match data.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameMap;
use App\Models\GameMapRound;
use App\Models\GamePlayerAdvancedStat;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\MatchVeto;
use App\Models\Player;
use App\Services\MapStatsCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApiGameMapController extends Controller
{
    public function __construct(private readonly MapStatsCalculator $mapStats) {}

    public function show(int $id, int $map_id): JsonResponse
    {
        $gameMap = GameMap::with([
            'playerStats.player:id,handle',
            'advancedStats.player:id,handle',
            'rounds' => fn ($q) => $q->orderBy('round_number'),
            'rounds.playerStats.player:id,handle',
        ])->where('match_id', $id)->findOrFail($map_id);

        return response()->json([
            'game_map' => $gameMap,
            'player_stats' => $gameMap->playerStats,
            'advanced_stats' => $gameMap->advancedStats,
            'rounds' => $gameMap->rounds->map(fn ($r) => [
                'round_number' => $r->round_number,
                'winning_team' => $r->winning_team,
                'win_type' => $r->win_type,
                'player_stats' => $r->playerStats->map(fn ($ps) => [
                    'player_id' => $ps->player_id,
                    'player_handle' => $ps->player?->handle,
                    'kills' => $ps->kills,
                    'assists' => $ps->assists,
                    'score' => $ps->score,
                    'loadout_value' => $ps->loadout_value,
                    'economy_spent' => $ps->economy_spent,
                    'economy_remaining' => $ps->economy_remaining,
                    'weapon_id' => $ps->weapon_id,
                    'armor' => $ps->armor,
                ]),
            ]),
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $gameMap = GameMap::with('match')->findOrFail($id);

        $validated = $request->validate([
            'api_match_id' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'team_a_score' => ['sometimes', 'nullable', 'integer'],
            'team_b_score' => ['sometimes', 'nullable', 'integer'],
            'order' => ['sometimes', 'integer'],
            'is_completed' => ['sometimes', 'boolean'],
        ]);

        $gameMap->update($validated);

        $match = $gameMap->match;
        $recomputed = $this->recomputeMatchScore($match);

        return response()->json([
            'success' => true,
            'game_map' => $gameMap->fresh(),
            'match' => $recomputed,
        ]);
    }

    public function storeStats(int $id, Request $request): JsonResponse
    {
        $gameMap = GameMap::with('match')->findOrFail($id);

        $validated = $request->validate([
            'player_stats' => ['required', 'array', 'min:1'],
            'player_stats.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'player_stats.*.team_id' => ['required', 'integer', 'exists:teams,id'],
            'player_stats.*.agent_name' => ['required', 'string', 'max:50'],
            'player_stats.*.kills' => ['required', 'integer', 'min:0'],
            'player_stats.*.deaths' => ['required', 'integer', 'min:0'],
            'player_stats.*.assists' => ['required', 'integer', 'min:0'],
            'player_stats.*.acs' => ['sometimes', 'numeric', 'min:0'],
            'player_stats.*.adr' => ['sometimes', 'numeric', 'min:0'],
            'player_stats.*.kast_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'player_stats.*.first_kills' => ['sometimes', 'integer', 'min:0'],
            'player_stats.*.first_deaths' => ['sometimes', 'integer', 'min:0'],
            'player_stats.*.headshot_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'rounds' => ['sometimes', 'array'],
            'rounds.*.round_number' => ['required_with:rounds', 'integer', 'min:1'],
            'rounds.*.winning_team' => ['required_with:rounds', 'integer', 'exists:teams,id'],
            'rounds.*.win_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'rounds.*.player_stats' => ['sometimes', 'array'],
            'rounds.*.player_stats.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'rounds.*.player_stats.*.kills' => ['sometimes', 'integer', 'min:0'],
            'rounds.*.player_stats.*.assists' => ['sometimes', 'integer', 'min:0'],
            'rounds.*.player_stats.*.score' => ['sometimes', 'integer', 'min:0'],
            'rounds.*.player_stats.*.loadout_value' => ['sometimes', 'integer', 'min:-1'],
            'rounds.*.player_stats.*.economy_spent' => ['sometimes', 'integer', 'min:-1'],
            'rounds.*.player_stats.*.economy_remaining' => ['sometimes', 'integer', 'min:-1'],
            'rounds.*.player_stats.*.weapon_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rounds.*.player_stats.*.armor' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $gameMap->playerStats()->delete();

        foreach ($validated['player_stats'] as $stat) {
            GamePlayerStat::create([
                'game_map_id' => $gameMap->id,
                'match_id' => $gameMap->match_id,
                'player_id' => $stat['player_id'],
                'team_id' => $stat['team_id'],
                'agent_name' => $stat['agent_name'],
                'kills' => $stat['kills'],
                'deaths' => $stat['deaths'],
                'assists' => $stat['assists'],
                'acs' => $stat['acs'] ?? 0,
                'adr' => $stat['adr'] ?? 0,
                'kast_percentage' => $stat['kast_percentage'] ?? 0,
                'first_kills' => $stat['first_kills'] ?? 0,
                'first_deaths' => $stat['first_deaths'] ?? 0,
                'headshot_percentage' => $stat['headshot_percentage'] ?? 0,
            ]);
        }

        if (isset($validated['rounds'])) {
            $gameMap->rounds()->delete();

            foreach ($validated['rounds'] as $roundData) {
                $round = GameMapRound::create([
                    'game_map_id' => $gameMap->id,
                    'round_number' => $roundData['round_number'],
                    'winning_team' => $roundData['winning_team'],
                    'win_type' => $roundData['win_type'] ?? null,
                ]);

                foreach ($roundData['player_stats'] ?? [] as $pStat) {
                    $round->playerStats()->create([
                        'player_id' => $pStat['player_id'],
                        'kills' => $pStat['kills'] ?? 0,
                        'assists' => $pStat['assists'] ?? 0,
                        'score' => $pStat['score'] ?? 0,
                        'loadout_value' => $pStat['loadout_value'] ?? 0,
                        'economy_spent' => $pStat['economy_spent'] ?? 0,
                        'economy_remaining' => $pStat['economy_remaining'] ?? 0,
                        'weapon_id' => $pStat['weapon_id'] ?? null,
                        'armor' => $pStat['armor'] ?? null,
                    ]);
                }
            }
        }

        $recomputed = $this->recomputeMatchScore($gameMap->match);

        return response()->json([
            'success' => true,
            'game_map' => $gameMap->fresh(),
            'match' => $recomputed,
        ]);
    }

    /**
     * Reset a map to its pristine, unplayed state: clears rounds, player
     * stats, scores and the linked Riot match, without deleting the map
     * itself (its name/order within the match are kept).
     */
    public function reset(int $id): JsonResponse
    {
        $gameMap = GameMap::with('match')->findOrFail($id);

        DB::transaction(function () use ($gameMap) {
            $gameMap->rounds()->delete();
            $gameMap->playerStats()->delete();
            $gameMap->advancedStats()->delete();

            $gameMap->update([
                'api_match_id' => null,
                'team_a_score' => null,
                'team_b_score' => null,
                'is_completed' => false,
            ]);
        });

        $recomputed = $this->recomputeMatchScore($gameMap->match);

        return response()->json([
            'success' => true,
            'game_map' => $gameMap->fresh(),
            'match' => $recomputed,
        ]);
    }

    public function assignPlayers(int $id, Request $request): JsonResponse
    {
        $gameMap = GameMap::findOrFail($id);

        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.val_id' => ['required', 'string'],
            'assignments.*.player_id' => ['required', 'integer', 'exists:players,id'],
        ]);

        foreach ($validated['assignments'] as $assignment) {
            try {
                Player::where('id', $assignment['player_id'])->update(['val_id' => $assignment['val_id']]);
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                $conflicting = Player::where('val_id', $assignment['val_id'])->first();

                return response()->json([
                    'error' => 'This val_id is already assigned to another player',
                    'val_id' => $assignment['val_id'],
                    'conflicting_player' => $conflicting,
                ], 422);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Recompute a match's team_a_score/team_b_score from its game maps.
     *
     * BO1: the score is the single map's score. BOx: the score is the
     * number of maps each side has won.
     */
    private function recomputeMatchScore($match): array
    {
        $maps = $match->game_maps()->orderBy('order')->get();

        if ($match->best_of <= 1) {
            $map = $maps->first();
            $scoreA = $map->team_a_score ?? 0;
            $scoreB = $map->team_b_score ?? 0;
        } else {
            $scoreA = $maps->filter(fn ($m) => ! is_null($m->team_a_score) && ! is_null($m->team_b_score) && $m->team_a_score > $m->team_b_score)->count();
            $scoreB = $maps->filter(fn ($m) => ! is_null($m->team_a_score) && ! is_null($m->team_b_score) && $m->team_b_score > $m->team_a_score)->count();
        }

        $match->update([
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
        ]);

        return $match->fresh()->toArray();
    }

    public function fetch(int $id, Request $request): Response|JsonResponse
    {
        $gameMap = GameMap::with(['match.tournament', 'match.teamA.players', 'match.teamB.players'])->find($id);

        if (! $gameMap) {
            return response()->json(['error' => 'Game map not found'], 404);
        }

        if (! $gameMap->api_match_id) {
            return response()->json(['error' => 'This game map has no associated Riot match ID'], 422);
        }

        $region = config('regions.riot_api.'.$gameMap->match->tournament->region);
        $isEsportEndpoint = false;

        $response = Http::withHeaders(['X-Riot-Token' => config('services.riot.key')])
            ->get("https://{$region}.api.riotgames.com/val/match/v1/matches/{$gameMap->api_match_id}");

        if (! $response->successful()) {
            $response = Http::withHeaders(['X-Riot-Token' => config('services.riot.key')])
                ->get("https://esports.api.riotgames.com/val/match/v1/matches/{$gameMap->api_match_id}");

            if (! $response->successful()) {
                return response()->json(['error' => 'Failed to fetch match data from the Riot API'], $response->status());
            }

            $isEsportEndpoint = true;
        }

        $validated = $request->validate([
            'puuid_mapping' => ['sometimes', 'array'],
            'puuid_mapping.*' => ['integer', 'exists:players,id'],
        ]);

        $puuidMapping = collect($validated['puuid_mapping'] ?? []);

        if ($isEsportEndpoint) {
            $cacheKey = $this->esportPuuidMappingCacheKey($gameMap->tournament_id);
            $puuidMapping = collect(Cache::get($cacheKey, []))->merge($puuidMapping);

            if ($puuidMapping->isNotEmpty()) {
                Cache::put($cacheKey, $puuidMapping->toArray(), now()->addDay());
            }
        }

        $apiMatch = $response->json();
        $content = $this->riotContent($region);

        $gameMap->update([
            'map_name' => $content['maps'][$apiMatch['matchInfo']['mapId']] ?? $gameMap->map_name,
        ]);

        $players = collect($apiMatch['players'])
            ->filter(fn ($p) => ! ($p['isObserver'] ?? false) && $p['stats'] !== null)
            ->values();

        $missingPlayers = $this->missingValIdPlayers($gameMap, $players, $content, $puuidMapping);

        if ($missingPlayers->isNotEmpty()) {
            return response()->json([
                'error' => 'Some players could not be matched to a team roster (missing val_id)',
                'missing_val_ids' => $missingPlayers,
                'is_esport_endpoint' => $isEsportEndpoint,
            ], 422);
        }

        $teams = collect($apiMatch['teams']);

        $teamAColor = $request->input('team_a_color')
            ?? $this->resolveTeamAColor($gameMap->match, $players, $teams);

        if (! $teamAColor) {
            return response()->json([
                'error' => 'Could not determine which team color corresponds to Team A/B',
                'available_colors' => $teams->pluck('teamId')->unique()->values(),
                'players' => $players->map(fn ($p) => [
                    'puuid' => $p['puuid'],
                    'name' => trim(($p['gameName'] ?? '').'#'.($p['tagLine'] ?? ''), '#'),
                    'agent' => $content['agents'][strtolower($p['characterId'] ?? '')] ?? $p['characterId'],
                    'team' => $p['teamId'],
                ])->values(),
            ], 422);
        }

        $this->storeMatchData($gameMap, $apiMatch, $region, $teamAColor, $puuidMapping);

        return response()->noContent($response->status());
    }

    private function esportPuuidMappingCacheKey(int $tournamentId): string
    {
        return "esport_puuid_mapping_tournament_{$tournamentId}";
    }

    private function missingValIdPlayers(GameMap $gameMap, Collection $players, array $content, Collection $puuidMapping): Collection
    {
        $match = $gameMap->match;

        $teamAPuuids = $match->teamA->currentPlayers()->whereNotNull('val_id')->pluck('val_id')->toArray();
        $assignedPuuids = Player::whereIn('val_id', $players->pluck('puuid'))->pluck('val_id')->toArray();
        $assignedPuuids = array_merge($assignedPuuids, $puuidMapping->keys()->toArray());

        $teamAColor = optional($players->first(fn ($p) => in_array($p['puuid'], $teamAPuuids)))['teamId'];

        return $players
            ->reject(fn ($p) => in_array($p['puuid'], $assignedPuuids))
            ->map(fn ($p) => [
                'puuid' => $p['puuid'],
                'name' => trim(($p['gameName'] ?? '').'#'.($p['tagLine'] ?? ''), '#'),
                'agent' => $content['agents'][strtolower($p['characterId'] ?? '')] ?? $p['characterId'],
                'team' => match (true) {
                    $teamAColor === null => $p['teamId'],
                    $p['teamId'] === $teamAColor => $match->teamA->short_name,
                    default => $match->teamB->short_name,
                },
            ])
            ->values();
    }

    private function resolveTeamAColor($match, Collection $players, Collection $teams): ?string
    {
        $teamAPuuids = $match->teamA->currentPlayers()->whereNotNull('val_id')->pluck('val_id')->toArray();
        $teamAColor = optional($players->first(fn ($p) => in_array($p['puuid'], $teamAPuuids)))['teamId'];

        if (! $teamAColor) {
            $teamBPuuids = $match->teamB->currentPlayers()->whereNotNull('val_id')->pluck('val_id')->toArray();
            $teamBColor = optional($players->first(fn ($p) => in_array($p['puuid'], $teamBPuuids)))['teamId'];
            $teamAColor = $teamBColor ? $teams->pluck('teamId')->first(fn ($color) => $color !== $teamBColor) : null;
        }

        return $teamAColor;
    }

    private function storeMatchData(GameMap $gameMap, array $apiMatch, string $region, string $teamAColor, Collection $puuidMapping): void
    {
        $match = $gameMap->match;
        $matchInfo = $apiMatch['matchInfo'];
        $players = collect($apiMatch['players'])
            ->filter(fn ($p) => ! ($p['isObserver'] ?? false) && $p['stats'] !== null)
            ->values();
        $teams = collect($apiMatch['teams']);
        $rounds = collect($apiMatch['roundResults']);
        $totalRounds = $rounds->count();

        $content = $this->riotContent($region);

        $scoreA = 0;
        $scoreB = 0;
        foreach ($teams as $team) {
            if ($team['teamId'] === $teamAColor) {
                $scoreA = $team['roundsWon'];
            } else {
                $scoreB = $team['roundsWon'];
            }
        }

        $gameMap->update([
            'api_match_id' => $matchInfo['matchId'],
            'map_name' => $content['maps'][$matchInfo['mapId']] ?? $gameMap->map_name,
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
            'is_completed' => $matchInfo['isCompleted'] ?? true,
        ]);

        $this->backfillVetoSide($gameMap, $rounds, $players, $teamAColor);

        $gameMap->rounds()->delete();
        $gameMap->advancedStats()->delete();

        $playerMapping = Player::whereIn('val_id', $players->pluck('puuid'))->pluck('id', 'val_id')
            ->union($puuidMapping);
        $roundKills = $rounds->map(fn ($round) => $this->mapStats->extractKills($round));

        foreach ($rounds as $index => $round) {
            $isTeamAWinner = ($round['winningTeam'] === $teamAColor);

            $gameMapRound = $gameMap->rounds()->create([
                'round_number' => $round['roundNum'] + 1,
                'winning_team' => $isTeamAWinner ? $match->team_a_id : $match->team_b_id,
                'win_type' => $round['roundResult'],
            ]);

            $kills = $roundKills[$index];

            $this->persistRoundKills($match, $gameMapRound, $kills, $playerMapping, $content);
            $this->persistRoundDamages($match, $gameMapRound, $round, $playerMapping);

            foreach ($round['playerStats'] as $pStat) {
                $puuid = $pStat['puuid'];

                if (! isset($playerMapping[$puuid])) {
                    continue;
                }

                $economy = $pStat['economy'] ?? [];

                $gameMapRound->playerStats()->create([
                    'player_id' => $playerMapping[$puuid],
                    'kills' => count($pStat['kills'] ?? []),
                    'assists' => $kills->filter(fn ($k) => in_array($puuid, $k['assistants']))->count(),
                    'score' => $pStat['score'] ?? 0,
                    'loadout_value' => $economy['loadoutValue'] ?? 0,
                    'economy_spent' => $economy['spent'] ?? 0,
                    'economy_remaining' => $economy['remaining'] ?? 0,
                    'weapon_id' => $content['equips'][strtolower($economy['weapon'] ?? '')] ?? 'None',
                    'armor' => $content['equips'][strtolower($economy['armor'] ?? '')] ?? 'None',
                ]);
            }
        }

        $this->saveMatchPlayerStats($gameMap, $players, $rounds, $roundKills, $teamAColor, $totalRounds, $content, $playerMapping);
        $this->computeAdvancedStats($gameMap, $players, $rounds, $roundKills, $teamAColor, $content, $playerMapping);
    }

    /**
     * Bulk-insert the raw kill events of a round into game_map_round_kills.
     */
    private function persistRoundKills(Matchs $match, GameMapRound $gameMapRound, Collection $kills, Collection $playerMapping, array $content): void
    {
        $now = now();

        $rows = $kills
            ->map(function ($kill) use ($match, $gameMapRound, $playerMapping, $content, $now) {
                $victimId = $playerMapping[$kill['victim']] ?? null;

                if (! $victimId) {
                    return null;
                }

                $assistantIds = collect($kill['assistants'])
                    ->map(fn ($puuid) => $playerMapping[$puuid] ?? null)
                    ->filter()
                    ->values();

                return [
                    'tournament_id' => $match->tournament_id,
                    'phase_id' => $match->phase_id,
                    'match_id' => $match->id,
                    'game_map_round_id' => $gameMapRound->id,
                    'killer_player_id' => $kill['killer'] !== null ? ($playerMapping[$kill['killer']] ?? null) : null,
                    'victim_player_id' => $victimId,
                    'time_ms' => $kill['time'],
                    'weapon' => $content['equips'][strtolower($kill['weapon'] ?? '')] ?? $kill['weapon'],
                    'damage_type' => $kill['damage_type'],
                    'is_secondary_fire' => $kill['is_secondary_fire'] ?? false,
                    'assistant_player_ids' => json_encode($assistantIds->values()->all()),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values();

        if ($rows->isNotEmpty()) {
            DB::table('game_map_round_kills')->insert($rows->toArray());
        }
    }

    /**
     * Bulk-insert the raw per-shot damage entries of a round into
     * game_map_round_damages.
     */
    private function persistRoundDamages(Matchs $match, GameMapRound $gameMapRound, array $round, Collection $playerMapping): void
    {
        $now = now();
        $rows = collect();

        foreach ($round['playerStats'] ?? [] as $pStat) {
            $attackerId = $playerMapping[$pStat['puuid']] ?? null;

            foreach ($pStat['damage'] ?? [] as $damage) {
                $receiverId = $playerMapping[$damage['receiver']] ?? null;

                if (! $receiverId) {
                    continue;
                }

                $rows->push([
                    'tournament_id' => $match->tournament_id,
                    'phase_id' => $match->phase_id,
                    'match_id' => $match->id,
                    'game_map_round_id' => $gameMapRound->id,
                    'attacker_player_id' => $attackerId,
                    'receiver_player_id' => $receiverId,
                    'damage' => $damage['damage'] ?? 0,
                    'headshots' => $damage['headshots'] ?? 0,
                    'bodyshots' => $damage['bodyshots'] ?? 0,
                    'legshots' => $damage['legshots'] ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($rows->isNotEmpty()) {
            DB::table('game_map_round_damages')->insert($rows->toArray());
        }
    }

    /**
     * Persist per-player KAST%/ACS/ADR/HS% for the map, computed by
     * MapStatsCalculator::computeBasicStats().
     */
    private function saveMatchPlayerStats(GameMap $gameMap, Collection $players, Collection $rounds, Collection $roundKills, ?string $teamAColor, int $totalRounds, array $content, Collection $playerMapping): void
    {
        $match = $gameMap->match;
        $basicStats = $this->mapStats->computeBasicStats($players, $rounds, $roundKills, $totalRounds);

        foreach ($players as $p) {
            $puuid = $p['puuid'];
            $user = isset($playerMapping[$puuid]) ? Player::find($playerMapping[$puuid]) : null;
            $teamId = ($p['teamId'] === $teamAColor) ? $match->team_a_id : $match->team_b_id;
            $agentName = $content['agents'][strtolower($p['characterId'] ?? '')] ?? $p['characterId'];
            $valName = trim(($p['gameName'] ?? '').'#'.($p['tagLine'] ?? ''), '#') ?: null;

            GamePlayerStat::updateOrCreate(
                ['game_map_id' => $gameMap->id, 'player_id' => $user?->id, 'agent_name' => $agentName],
                array_merge([
                    'match_id' => $match->id,
                    'team_id' => $teamId,
                    'player_id' => $user?->id,
                    'agent_name' => $agentName,
                    'val_name' => $valName,
                ], $basicStats[$puuid])
            );
        }
    }

    /**
     * Persist per-player advanced stats for the map (clutches, multi-kills,
     * trades, economy round outcomes, plants/defuses/post-plant, ATK/DEF
     * splits), computed by MapStatsCalculator::computeAdvancedStats().
     */
    private function computeAdvancedStats(GameMap $gameMap, Collection $players, Collection $rounds, Collection $roundKills, ?string $teamAColor, array $content, Collection $playerMapping): void
    {
        $match = $gameMap->match;
        $agg = $this->mapStats->computeAdvancedStats($players, $rounds, $roundKills, $teamAColor);

        foreach ($players as $p) {
            $puuid = $p['puuid'];
            $playerId = $playerMapping[$puuid] ?? null;
            $agentName = $content['agents'][strtolower($p['characterId'] ?? '')] ?? $p['characterId'];

            GamePlayerAdvancedStat::updateOrCreate(
                ['game_map_id' => $gameMap->id, 'player_id' => $playerId, 'agent_name' => $agentName],
                array_merge(['match_id' => $match->id], $agg[$puuid])
            );
        }
    }

    /**
     * Determine the map's veto side (and who picked it) from the actual
     * round data, for vetos saved without a side because the operator
     * didn't enter one.
     */
    private function backfillVetoSide(GameMap $gameMap, Collection $rounds, Collection $players, ?string $teamAColor): void
    {
        if (! $teamAColor) {
            return;
        }

        $match = $gameMap->match;

        $veto = MatchVeto::where('match_id', $match->id)
            ->where('map_name', $gameMap->map_name)
            ->whereIn('type', ['pick', 'decider'])
            ->whereNull('side')
            ->first();

        if (! $veto) {
            return;
        }

        $attackerColor = $this->mapStats->firstHalfAttackerColor($rounds, $players);

        if (! $attackerColor) {
            return;
        }

        $attackingTeamId = $attackerColor === $teamAColor ? $match->team_a_id : $match->team_b_id;
        $pickedById = $veto->team_id === $match->team_a_id ? $match->team_b_id : $match->team_a_id;

        $veto->update([
            'side' => $pickedById === $attackingTeamId ? 'atk' : 'def',
            'side_picked_by' => $pickedById,
        ]);
    }

    private function riotContent(string $region): array
    {
        return Cache::remember("riot_content_{$region}", now()->addDay(), function () use ($region) {
            $response = Http::withHeaders(['X-Riot-Token' => config('services.riot.key')])
                ->get("https://{$region}.api.riotgames.com/val/content/v1/contents", ['locale' => 'en-US']);

            $data = $response->successful() ? $response->json() : [];

            return [
                'maps' => collect($data['maps'] ?? [])->pluck('name', 'assetPath')->toArray(),
                'agents' => collect($data['characters'] ?? [])->mapWithKeys(fn ($c) => [strtolower($c['id']) => $c['name']])->toArray(),
                'equips' => collect($data['equips'] ?? [])->mapWithKeys(fn ($c) => [strtolower($c['id']) => $c['name']])->toArray(),
            ];
        });
    }
}
