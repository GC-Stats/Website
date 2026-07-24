<?php

/**
 * GC-Stats — Admin: game maps
 *
 * Per-map actions nested under a match: basic field edits, live fetch from
 * the Riot relay (fetchMapData() — transferred from the deprecated
 * Api\ApiGameMapController, also reused by
 * App\Console\Commands\BackfillMapAdvancedStats — this is now the single
 * source of truth, not duplicated elsewhere),
 * cache renew (POST {relay}/match/{region}/{api_match_id}/renew, the
 * per-map equivalent of the `val:matches:renew` console command), reset,
 * and hard delete. Every mutating action requires the `.finished` sibling
 * of its own permission (e.g. `maps.delete.finished`) once the parent
 * match/tournament is finished — see requireEditable().
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\ApiGameMapController;
use App\Http\Controllers\Controller;
use App\Models\GameMap;
use App\Models\GameMapRound;
use App\Models\GameMapRoundPlayerStat;
use App\Models\GamePlayerAdvancedStat;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\MatchVeto;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\MapStatsCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class GameMapController extends Controller
{
    public function __construct(private readonly MapStatsCalculator $mapStats) {}

    public function show(Tournament $tournament, Matchs $match, GameMap $map): View
    {
        $this->ensureNesting($tournament, $match, $map);

        $match->load(['teamA', 'teamB']);
        $map->load(['playerStats.player:id,handle', 'rounds.playerStats']);

        $teamAPlayers = $match->teamA?->currentPlayers()->get(['players.id', 'players.handle', 'players.country_code']) ?? collect();
        $teamBPlayers = $match->teamB?->currentPlayers()->get(['players.id', 'players.handle', 'players.country_code']) ?? collect();

        $rosterPlayers = $teamAPlayers->concat($teamBPlayers)->unique('id')->values();

        $pickerPlayers = $rosterPlayers->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle])
            ->concat($map->playerStats->pluck('player')->filter()->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle]))
            ->unique('id')
            ->values();

        return view('admin.matches.maps.show', [
            'tournament' => $tournament,
            'match' => $match,
            'map' => $map,
            'rosterPlayers' => $rosterPlayers,
            'teamAPlayers' => $teamAPlayers->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle])->values(),
            'teamBPlayers' => $teamBPlayers->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle])->values(),
            'pickerPlayers' => $pickerPlayers,
            'agentPool' => config('valorant.agents'),
            'weaponPool' => config('valorant.weapons'),
            'armorPool' => config('valorant.armor_types'),
            'winTypePool' => config('valorant.win_types'),
        ]);
    }

    /**
     * Manual stat entry — replaces this map's player stats (and, if
     * provided, its rounds) wholesale from the submitted form. Used when a
     * map has no linked Riot match ID to fetch from (e.g. LAN matches, or
     * matches too old for the relay's cache).
     */
    public function updateStats(Request $request, Tournament $tournament, Matchs $match, GameMap $map): RedirectResponse|JsonResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.edit', $map);

        $validated = $request->validate([
            'player_stats' => ['required', 'array', 'min:1'],
            'player_stats.*.player_id' => ['required', 'integer'],
            'player_stats.*.team_id' => ['required', 'integer', 'in:'.$match->team_a_id.','.$match->team_b_id],
            'player_stats.*.agent_name' => ['required', 'string', 'max:50'],
            'player_stats.*.kills' => ['required', 'integer', 'min:0'],
            'player_stats.*.deaths' => ['required', 'integer', 'min:0'],
            'player_stats.*.assists' => ['required', 'integer', 'min:0'],
            'player_stats.*.acs' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'player_stats.*.adr' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'player_stats.*.kast_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'player_stats.*.first_kills' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'player_stats.*.first_deaths' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'player_stats.*.headshot_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'rounds' => ['sometimes', 'array'],
            'rounds.*.round_number' => ['required_with:rounds', 'integer', 'min:1'],
            'rounds.*.winning_team' => ['required_with:rounds', 'integer', 'in:'.$match->team_a_id.','.$match->team_b_id],
            'rounds.*.win_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'rounds.*.player_stats' => ['sometimes', 'array'],
            'rounds.*.player_stats.*.player_id' => ['required', 'integer'],
            'rounds.*.player_stats.*.kills' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rounds.*.player_stats.*.assists' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rounds.*.player_stats.*.score' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rounds.*.player_stats.*.loadout_value' => ['sometimes', 'nullable', 'integer', 'min:-1'],
            'rounds.*.player_stats.*.economy_spent' => ['sometimes', 'nullable', 'integer', 'min:-1'],
            'rounds.*.player_stats.*.economy_remaining' => ['sometimes', 'nullable', 'integer', 'min:-1'],
            'rounds.*.player_stats.*.weapon_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rounds.*.player_stats.*.armor' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $match, $map) {
            $map->playerStats()->delete();

            foreach ($validated['player_stats'] as $stat) {
                GamePlayerStat::create([
                    ...$stat,
                    'match_id' => $match->id,
                    'game_map_id' => $map->id,
                ]);
            }

            if (array_key_exists('rounds', $validated)) {
                $map->rounds()->delete();

                foreach ($validated['rounds'] as $roundData) {
                    $round = GameMapRound::create([
                        'game_map_id' => $map->id,
                        'round_number' => $roundData['round_number'],
                        'winning_team' => $roundData['winning_team'],
                        'win_type' => $roundData['win_type'] ?? 'Eliminated',
                    ]);

                    foreach ($roundData['player_stats'] ?? [] as $ps) {
                        GameMapRoundPlayerStat::create([
                            ...$ps,
                            'game_map_round_id' => $round->id,
                        ]);
                    }
                }
            }
        });

        activity('tournament')->causedBy($request->user())
            ->performedOn($map)->log('map.stats_updated');

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.matches.maps.show', [$tournament, $match, $map])->with('status', 'map-stats-updated');
    }

    public function update(Request $request, Tournament $tournament, Matchs $match, GameMap $map): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.edit', $map);

        $validated = $request->validate([
            'api_match_id' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('game_maps', 'api_match_id')->ignore($map->id)],
            'map_name' => ['sometimes', 'string', 'max:50'],
            'team_a_score' => ['sometimes', 'nullable', 'integer'],
            'team_b_score' => ['sometimes', 'nullable', 'integer'],
            'order' => ['sometimes', 'integer'],
            'is_completed' => ['sometimes', 'boolean'],
        ], [
            'api_match_id.unique' => __('admin.matches.maps.api_match_id_duplicate'),
        ]);

        $map->update($validated);

        $this->recomputeMatchScore($match);

        activity('tournament')->causedBy($request->user())
            ->performedOn($map)->log('map.updated');

        return redirect()->route('admin.matches.maps.show', [$tournament, $match, $map])->with('status', 'map-updated');
    }

    /**
     * Recompute the match's team_a_score/team_b_score from its game maps,
     * then, once the series is decided, auto-skip any remaining unplayed
     * maps and flip the match to finished. Manual "basic info" edits are
     * the surviving way to record a map's score once the Riot relay fetch
     * is retired.
     *
     * BO1: decided once the single map is complete. BOx: decided once one
     * side reaches the majority of maps (2 for BO3, 3 for BO5). Skipped
     * maps get score -1/-1 + completed, mirroring
     * MatchController::importWikicode()'s manual "skip" convention.
     */
    private function recomputeMatchScore(Matchs $match): void
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

        $requiredWins = intdiv(max((int) $match->best_of, 1), 2) + 1;

        $decided = $match->best_of <= 1
            ? (bool) optional($maps->first())->is_completed
            : ($scoreA >= $requiredWins || $scoreB >= $requiredWins);

        if (! $decided) {
            return;
        }

        $maps->reject(fn ($m) => $m->is_completed)
            ->each(fn ($m) => $m->update([
                'team_a_score' => -1,
                'team_b_score' => -1,
                'is_completed' => true,
            ]));

        if ($match->status !== 'finished') {
            $match->update(['status' => 'finished']);
        }
    }

    /**
     * The map show page drives this via JS `fetch()` (Accept: application/json)
     * so that a 422 asking for more input (missing val_id / team-color
     * ambiguity — see fetchMapData()) can be resolved inline
     * and resubmitted automatically, instead of a full page reload + the
     * operator having to manually re-click Fetch. A classic form POST
     * (no JS) still falls back to the redirect behavior below.
     */
    public function fetch(Request $request, Tournament $tournament, Matchs $match, GameMap $map): RedirectResponse|JsonResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.fetch', $map);

        $response = $this->fetchMapData($map->id, $request);
        $status = $response->getStatusCode();
        $succeeded = $status >= 200 && $status < 300;

        if ($succeeded) {
            $this->recomputeMatchScore($match);
        }

        activity('tournament')->causedBy($request->user())
            ->performedOn($map)->log('map.fetched');

        if ($request->wantsJson()) {
            if ($succeeded) {
                return response()->json(['success' => true]);
            }

            return response()->json(json_decode($response->getContent(), true) ?? [], $status);
        }

        if ($succeeded) {
            return redirect()->route('admin.matches.maps.show', [$tournament, $match, $map])->with('status', 'map-fetched');
        }

        $payload = json_decode($response->getContent(), true) ?? [];

        return back()->with('error', 'map-fetch-failed')->with('fetchError', $payload);
    }

    /**
     * Fetches and computes detailed per-map statistics (rounds, trades,
     * player stats) for a given game map by calling the Riot match API and
     * processing the raw match data. Transferred from the deprecated
     * Api\ApiGameMapController — this is now the single source of truth,
     * also reused by App\Console\Commands\BackfillMapAdvancedStats.
     */
    public function fetchMapData(int $id, Request $request): Response|JsonResponse
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
        $relayUrl = rtrim(config('services.riot.relay_url'), '/');

        $response = Http::withHeaders(['Authorization' => config('services.riot.relay_token')])
            ->get("{$relayUrl}/match/{$region}/{$gameMap->api_match_id}");

        if (! $response->successful()) {
            $response = Http::withHeaders(['Authorization' => config('services.riot.relay_token')])
                ->get("{$relayUrl}/match/esports/{$gameMap->api_match_id}");

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

        $this->persistPuuidMapping($puuidMapping, $isEsportEndpoint);

        $apiMatch = $response->json();
        $content = $this->riotContent($region);

        $gameMap->update([
            'map_name' => $content['maps'][$apiMatch['matchInfo']['mapId']] ?? $gameMap->map_name,
        ]);

        $players = collect($apiMatch['players'])
            ->filter(fn ($p) => ! ($p['isObserver'] ?? false) && $p['stats'] !== null)
            ->values();

        $missingPlayers = $this->missingValIdPlayers($gameMap, $players, $content, $puuidMapping, $isEsportEndpoint);

        if ($missingPlayers->isNotEmpty()) {
            return response()->json([
                'error' => 'Some players could not be matched to a team roster (missing val_id)',
                'missing_val_ids' => $missingPlayers,
                'is_esport_endpoint' => $isEsportEndpoint,
            ], 422);
        }

        $teams = collect($apiMatch['teams']);

        $teamAColor = $request->input('team_a_color')
            ?? $this->resolveTeamAColor($gameMap->match, $players, $teams, $isEsportEndpoint);

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

        $this->storeMatchData($gameMap, $apiMatch, $region, $teamAColor, $puuidMapping, $isEsportEndpoint);

        return response()->noContent($response->status());
    }

    /**
     * Persist puuid => player_id assignments the operator resolved from the
     * "missing val_id" form onto the matching Player column, so the next
     * fetch (this map or any other) recognizes those players automatically
     * instead of asking again. Uses esports_val_id for the esports endpoint
     * since its puuids are distinct from the regular match API's.
     */
    private function persistPuuidMapping(Collection $puuidMapping, bool $isEsportEndpoint): void
    {
        if ($puuidMapping->isEmpty()) {
            return;
        }

        $column = $isEsportEndpoint ? 'esports_val_id' : 'val_id';

        foreach ($puuidMapping as $puuid => $playerId) {
            try {
                Player::where('id', $playerId)->whereNull($column)->update([$column => $puuid]);
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
    }

    private function missingValIdPlayers(GameMap $gameMap, Collection $players, array $content, Collection $puuidMapping, bool $isEsportEndpoint): Collection
    {
        $column = $isEsportEndpoint ? 'esports_val_id' : 'val_id';
        $match = $gameMap->match;

        $teamAPuuids = $match->teamA->currentPlayers()->whereNotNull($column)->pluck($column)->toArray();
        $assignedPuuids = Player::whereIn($column, $players->pluck('puuid'))->pluck($column)->toArray();
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

    private function resolveTeamAColor($match, Collection $players, Collection $teams, bool $isEsportEndpoint): ?string
    {
        $column = $isEsportEndpoint ? 'esports_val_id' : 'val_id';

        $teamAPuuids = $match->teamA->currentPlayers()->whereNotNull($column)->pluck($column)->toArray();
        $teamAColor = optional($players->first(fn ($p) => in_array($p['puuid'], $teamAPuuids)))['teamId'];

        if (! $teamAColor) {
            $teamBPuuids = $match->teamB->currentPlayers()->whereNotNull($column)->pluck($column)->toArray();
            $teamBColor = optional($players->first(fn ($p) => in_array($p['puuid'], $teamBPuuids)))['teamId'];
            $teamAColor = $teamBColor ? $teams->pluck('teamId')->first(fn ($color) => $color !== $teamBColor) : null;
        }

        return $teamAColor;
    }

    private function storeMatchData(GameMap $gameMap, array $apiMatch, string $region, string $teamAColor, Collection $puuidMapping, bool $isEsportEndpoint): void
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

        $column = $isEsportEndpoint ? 'esports_val_id' : 'val_id';
        $playerMapping = Player::whereIn($column, $players->pluck('puuid'))->pluck('id', $column)
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

    /**
     * Per-map version of App\Console\Commands\RenewAllMatches: refreshes the
     * Riot relay's cache for this map's linked match, falling back to the
     * esports endpoint on failure.
     */
    public function renew(Request $request, Tournament $tournament, Matchs $match, GameMap $map): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.cache.renew', $map);

        abort_unless($map->api_match_id, 422, 'This map has no linked Riot match ID.');

        $region = config('regions.riot_api.'.$tournament->region);
        $relayUrl = rtrim(config('services.riot.relay_url'), '/');
        $headers = ['Authorization' => config('services.riot.relay_token')];

        $response = Http::withHeaders($headers)->post("{$relayUrl}/match/{$region}/{$map->api_match_id}/renew");

        if (! $response->successful()) {
            $response = Http::withHeaders($headers)->post("{$relayUrl}/match/esports/{$map->api_match_id}/renew");
        }

        activity('tournament')->causedBy($request->user())
            ->performedOn($map)->log('map.cache_renewed');

        return back()->with($response->successful() ? 'status' : 'error', $response->successful() ? 'map-renewed' : 'map-renew-failed');
    }

    public function reset(Request $request, Tournament $tournament, Matchs $match, GameMap $map, ApiGameMapController $api): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.reset', $map);

        $api->reset($map->id);

        activity('tournament')->causedBy($request->user())
            ->performedOn($map)->log('map.reset');

        return redirect()->route('admin.matches.maps.show', [$tournament, $match, $map])->with('status', 'map-reset');
    }

    public function destroy(Request $request, Tournament $tournament, Matchs $match, GameMap $map): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.delete', $map);

        $map->delete();

        activity('tournament')->causedBy($request->user())->log('map.deleted');

        return redirect()->route('admin.matches.show', [$tournament, $match])->with('status', 'map-deleted');
    }

    private function requireEditable(Request $request, Tournament $tournament, Matchs $match, string $permission, ?GameMap $map = null): void
    {
        if ($map) {
            $this->ensureNesting($tournament, $match, $map);
        } else {
            abort_unless($match->tournament_id === $tournament->id, 404);
        }

        abort_unless(
            ! MatchController::isFinished($tournament, $match) || $request->user()->can("{$permission}.finished"),
            403,
            "Only a user with {$permission}.finished can edit maps of a finished match."
        );
    }

    /**
     * {match} and {map} bind by id alone, independent of the URL's parent
     * segments — without this, a crafted URL mixing a map/match from a
     * different tournament (e.g. a non-finished one) could bypass the
     * finished-tournament `.finished` gate above or write stats/config for
     * the wrong map/match.
     */
    private function ensureNesting(Tournament $tournament, Matchs $match, GameMap $map): void
    {
        abort_unless($match->tournament_id === $tournament->id, 404);
        abort_unless($map->match_id === $match->id, 404);
    }
}
