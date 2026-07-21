<?php

/**
 * GC-Stats — Admin: game maps
 *
 * Per-map actions nested under a match: basic field edits, live fetch from
 * the Riot relay (delegates to Api\ApiGameMapController — the stat
 * computation there is the single source of truth, not duplicated here),
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
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\Tournament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GameMapController extends Controller
{
    public function show(Tournament $tournament, Matchs $match, GameMap $map): View
    {
        $this->ensureNesting($tournament, $match, $map);

        $match->load(['teamA', 'teamB']);
        $map->load(['playerStats.player:id,handle', 'rounds.playerStats']);

        $teamAPlayers = $match->teamA?->currentPlayers()->get(['players.id', 'players.handle']) ?? collect();
        $teamBPlayers = $match->teamB?->currentPlayers()->get(['players.id', 'players.handle']) ?? collect();

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
            'api_match_id' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'map_name' => ['sometimes', 'string', 'max:50'],
            'team_a_score' => ['sometimes', 'nullable', 'integer'],
            'team_b_score' => ['sometimes', 'nullable', 'integer'],
            'order' => ['sometimes', 'integer'],
            'is_completed' => ['sometimes', 'boolean'],
        ]);

        $map->update($validated);

        activity('tournament')->causedBy($request->user())
            ->performedOn($map)->log('map.updated');

        return redirect()->route('admin.matches.maps.show', [$tournament, $match, $map])->with('status', 'map-updated');
    }

    /**
     * The map show page drives this via JS `fetch()` (Accept: application/json)
     * so that a 422 asking for more input (missing val_id / team-color
     * ambiguity — see ApiGameMapController::fetch()) can be resolved inline
     * and resubmitted automatically, instead of a full page reload + the
     * operator having to manually re-click Fetch. A classic form POST
     * (no JS) still falls back to the redirect behavior below.
     */
    public function fetch(Request $request, Tournament $tournament, Matchs $match, GameMap $map, ApiGameMapController $api): RedirectResponse|JsonResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.fetch', $map);

        $response = $api->fetch($map->id, $request);
        $status = $response->getStatusCode();

        activity('tournament')->causedBy($request->user())
            ->performedOn($map)->log('map.fetched');

        if ($request->wantsJson()) {
            if ($status >= 200 && $status < 300) {
                return response()->json(['success' => true]);
            }

            return response()->json(json_decode($response->getContent(), true) ?? [], $status);
        }

        if ($status >= 200 && $status < 300) {
            return redirect()->route('admin.matches.maps.show', [$tournament, $match, $map])->with('status', 'map-fetched');
        }

        $payload = json_decode($response->getContent(), true) ?? [];

        return back()->with('error', 'map-fetch-failed')->with('fetchError', $payload);
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
