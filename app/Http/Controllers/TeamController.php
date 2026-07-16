<?php

/**
 * GC-Stats — Team page controller
 *
 * Renders the team profile page (roster, transactions, stats, match history)
 * and caches the assembled data per team for one day, invalidated by TeamObserver.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Models\GameMap;
use App\Models\Matchs;
use App\Models\News;
use App\Models\Team;
use App\Support\MatchPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Redirects to the canonical slugged URL when the incoming slug is
     * missing or stale, so search engines only ever see one URL per team.
     */
    private function redirectToCanonicalSlug(int $id, ?string $slug, string $routeName)
    {
        $name = Team::where('id', $id)->value('name');
        abort_unless($name !== null, 404);

        $canonical = Str::routeSlug($name, $id);
        if ($slug !== $canonical) {
            return redirect()->route($routeName, [$id, $canonical], 301);
        }

        return null;
    }

    public function index(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'teams.show')) {
            return $redirect;
        }

        $cacheKey = "team_page_{$id}";
        $tag = "team_{$id}";

        $data = Cache::tags([$tag, 'teams'])->remember($cacheKey, now()->addDay(), function () use ($id) {
            $team = Team::findOrFail($id);

            $roleOrder = ['player' => 0, 'sub' => 1, 'head coach' => 2, 'assistant coach' => 3, 'manager' => 4, 'staff' => 5];

            $currentRoster = $team->players()
                ->select('players.id', 'players.handle')
                ->withPivot('role', 'joined_at', 'left_at')
                ->wherePivotNull('left_at')
                ->get()
                ->sortBy(fn ($player) => $roleOrder[strtolower($player->pivot->role ?? '')] ?? 99)
                ->values()
                ->toArray();

            $pastPlayers = $team->players()
                ->select('players.id', 'players.handle')
                ->withPivot('role', 'joined_at', 'left_at')
                ->wherePivotNotNull('left_at')
                ->orderByPivot('left_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray();

            $matchesRaw = $this->teamMatchesQuery($id)
                ->orderBy('matches.scheduled_at', 'desc')
                ->limit(10)
                ->get();

            $processedMatches = $matchesRaw
                ->map(fn ($match) => $this->formatTeamMatch($match, $id))
                ->all();

            return [
                'team' => $team->makeHidden(['players'])->toArray(),
                'currentRoster' => $currentRoster,
                'pastPlayers' => $pastPlayers,
                'matches' => $processedMatches,
            ];
        });

        $news = News::with(['author', 'publisher'])
            ->published()
            ->forLocale(app()->getLocale())
            ->whereHas('teams', fn ($q) => $q->where('teams.id', $id))
            ->latest('published_at')
            ->take(3)
            ->get()
            ->toArray();

        return response()
            ->view('team.index', array_merge($data, ['news' => $news]))
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function history(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'teams.history')) {
            return $redirect;
        }

        $page = $request->input('page', 1);
        $cacheKey = "team_history_{$id}_page_{$page}";
        $tag = "team_{$id}";

        $data = Cache::tags([$tag, 'teams'])->remember($cacheKey, now()->addDay(), function () use ($id) {
            $team = Team::findOrFail($id);

            $paginated = $team->players()
                ->select('players.id', 'players.handle')
                ->withPivot('role', 'joined_at', 'left_at')
                ->wherePivotNotNull('left_at')
                ->orderByPivot('left_at', 'desc')
                ->paginate(10);

            return [
                'team' => $team->toArray(),
                'pastPlayers' => collect($paginated->items())->map->toArray()->all(),
                'meta' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                ],
            ];
        });

        $pastPlayers = new LengthAwarePaginator(
            $data['pastPlayers'],
            $data['meta']['total'],
            $data['meta']['per_page'],
            $data['meta']['current_page'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()
            ->view('team.history', ['team' => $data['team'], 'pastPlayers' => $pastPlayers])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function matches(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'teams.matches')) {
            return $redirect;
        }

        $page = $request->input('page', 1);
        $cacheKey = "team_page_matches_{$id}_page_{$page}";
        $tag = "team_{$id}";

        $cached = Cache::tags([$tag])->get($cacheKey);
        if ($cached) {
            $matches = new LengthAwarePaginator(
                $cached['matches'],
                $cached['meta']['total'],
                $cached['meta']['per_page'],
                $cached['meta']['current_page'],
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()
                ->view('team.matches', ['team' => $cached['team'], 'matches' => $matches])
                ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
                ->header('Vary', 'Accept-Language');
        }

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id) {
            $team = Team::findOrFail($id);

            $paginated = $this->teamMatchesQuery($id)
                ->orderBy('matches.scheduled_at', 'desc')
                ->paginate(10);

            $matchesArray = collect($paginated->items())
                ->map(fn ($match) => $this->formatTeamMatch($match, $id))
                ->all();

            return [
                'team' => $team->toArray(),
                'matches' => $matchesArray,
                'meta' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                ],
            ];
        });

        $matches = new LengthAwarePaginator(
            $data['matches'],
            $data['meta']['total'],
            $data['meta']['per_page'],
            $data['meta']['current_page'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()
            ->view('team.matches', ['team' => $data['team'], 'matches' => $matches])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function maps(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'teams.maps')) {
            return $redirect;
        }

        $cacheKey = "team_maps_{$id}";
        $tag = "team_{$id}";

        $data = Cache::tags([$tag, 'teams'])->remember($cacheKey, now()->addDay(), function () use ($id) {
            $team = Team::findOrFail($id);

            $mapRows = GameMap::query()
                ->join('matches as m', 'm.id', '=', 'game_maps.match_id')
                ->where('game_maps.is_completed', true)
                ->whereNotNull('game_maps.map_name')
                ->where(function ($query) use ($id) {
                    $query->where('m.team_a_id', $id)->orWhere('m.team_b_id', $id);
                })
                ->select('game_maps.id', 'game_maps.map_name', 'game_maps.team_a_score', 'game_maps.team_b_score', 'm.team_a_id', 'm.team_b_id')
                ->get();

            $playedCounts = $mapRows->groupBy('map_name')->map->count();

            $wins = $mapRows->groupBy('map_name')->map(function ($group) use ($id) {
                return $group->filter(function ($map) use ($id) {
                    $isTeamA = $map->team_a_id == $id;
                    $ownScore = $isTeamA ? $map->team_a_score : $map->team_b_score;
                    $oppScore = $isTeamA ? $map->team_b_score : $map->team_a_score;

                    return ($ownScore ?? 0) > ($oppScore ?? 0);
                })->count();
            });

            $winrates = DB::table('game_player_advanced_stats as apas')
                ->join('game_player_stats as gps', function ($join) {
                    $join->on('gps.game_map_id', '=', 'apas.game_map_id')
                        ->on('gps.player_id', '=', 'apas.player_id');
                })
                ->join('game_maps as gm', 'gm.id', '=', 'apas.game_map_id')
                ->where('gm.is_completed', true)
                ->whereNotNull('gm.map_name')
                ->where('gps.team_id', $id)
                ->groupBy('gm.map_name')
                ->selectRaw('
                    gm.map_name,
                    SUM(apas.atk_rounds) as atk_rounds,
                    SUM(apas.atk_rounds_won) as atk_rounds_won,
                    SUM(apas.def_rounds) as def_rounds,
                    SUM(apas.def_rounds_won) as def_rounds_won
                ')
                ->get()
                ->keyBy('map_name');

            $compRows = DB::table('game_player_stats as gps')
                ->join('game_maps as gm', 'gm.id', '=', 'gps.game_map_id')
                ->join('matches as m', 'm.id', '=', 'gm.match_id')
                ->leftJoin('teams as ta', 'ta.id', '=', 'm.team_a_id')
                ->leftJoin('teams as tb', 'tb.id', '=', 'm.team_b_id')
                ->where('gm.is_completed', true)
                ->whereNotNull('gm.map_name')
                ->where('gps.team_id', $id)
                ->select(
                    'gm.map_name', 'gm.id as game_map_id', 'gm.team_a_score', 'gm.team_b_score',
                    'm.team_a_id', 'm.team_b_id', 'm.id as match_id', 'm.scheduled_at',
                    'ta.name as team_a_name', 'tb.name as team_b_name', 'gps.agent_name'
                )
                ->get();

            ['comps' => $compsByMap, 'pick_rates' => $pickRatesByMap] = $this->buildTeamMapComps($compRows, (int) $id);

            $maps = collect($playedCounts->keys())->map(function ($mapName) use ($playedCounts, $wins, $winrates, $compsByMap, $pickRatesByMap) {
                $wr = $winrates->get($mapName);
                $atkRounds = (int) ($wr->atk_rounds ?? 0);
                $atkWon = (int) ($wr->atk_rounds_won ?? 0);
                $defRounds = (int) ($wr->def_rounds ?? 0);
                $defWon = (int) ($wr->def_rounds_won ?? 0);

                return [
                    'map_name' => $mapName,
                    'times_played' => $playedCounts[$mapName],
                    'wins' => $wins[$mapName] ?? 0,
                    'losses' => $playedCounts[$mapName] - ($wins[$mapName] ?? 0),
                    'atk_win_pct' => $atkRounds > 0 ? round($atkWon / $atkRounds * 100, 1) : null,
                    'def_win_pct' => $defRounds > 0 ? round($defWon / $defRounds * 100, 1) : null,
                    'comps' => $compsByMap[$mapName] ?? [],
                    'pick_rates' => $pickRatesByMap[$mapName] ?? [],
                ];
            })->sortByDesc('times_played')->values();

            return [
                'team' => $team->toArray(),
                'maps' => $maps->toArray(),
            ];
        });

        return response()
            ->view('team.maps', ['team' => $data['team'], 'maps' => $data['maps']])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Group raw (map, game_map, agent) rows into 5-agent compositions played
     * by this team, keeping each comp's play count, win rate, and the list
     * of matches it was played in, keyed by map name. Also derives each
     * agent's pick rate per map (share of this team's drafts on that map
     * that included them).
     */
    private function buildTeamMapComps($rows, int $teamId): array
    {
        $comps = [];

        foreach ($rows->groupBy('game_map_id') as $group) {
            $first = $group->first();
            $agents = $group->pluck('agent_name')->unique()->sort()->values()->all();

            if (count($agents) < 5) {
                continue;
            }

            $compKey = $first->map_name.'|'.implode(',', $agents);
            $isTeamA = $first->team_a_id == $teamId;
            $ownScore = $isTeamA ? $first->team_a_score : $first->team_b_score;
            $oppScore = $isTeamA ? $first->team_b_score : $first->team_a_score;
            $opponentName = $isTeamA ? $first->team_b_name : $first->team_a_name;
            $won = ($ownScore ?? 0) > ($oppScore ?? 0);

            if (! isset($comps[$compKey])) {
                $comps[$compKey] = [
                    'map_name' => $first->map_name,
                    'agents' => $agents,
                    'count' => 0,
                    'wins' => 0,
                    'matches' => [],
                ];
            }

            $comps[$compKey]['count']++;
            if ($won) {
                $comps[$compKey]['wins']++;
            }

            $comps[$compKey]['matches'][] = [
                'match_id' => $first->match_id,
                'opponent' => $opponentName,
                'own_score' => (int) ($ownScore ?? 0),
                'opp_score' => (int) ($oppScore ?? 0),
                'won' => $won,
                'scheduled_at' => $first->scheduled_at,
            ];
        }

        $byMap = [];
        $pickCountsByMap = [];
        $instancesByMap = [];

        foreach ($comps as $comp) {
            $comp['win_pct'] = $comp['count'] > 0 ? round($comp['wins'] / $comp['count'] * 100, 1) : null;
            usort($comp['matches'], fn ($a, $b) => strcmp($b['scheduled_at'] ?? '', $a['scheduled_at'] ?? ''));
            $byMap[$comp['map_name']][] = $comp;

            $instancesByMap[$comp['map_name']] = ($instancesByMap[$comp['map_name']] ?? 0) + $comp['count'];
            foreach ($comp['agents'] as $agent) {
                $pickCountsByMap[$comp['map_name']][$agent] = ($pickCountsByMap[$comp['map_name']][$agent] ?? 0) + $comp['count'];
            }
        }

        foreach ($byMap as $mapName => $list) {
            usort($list, fn ($a, $b) => $b['count'] <=> $a['count']);
            $byMap[$mapName] = $list;
        }

        $pickRatesByMap = [];
        foreach ($pickCountsByMap as $mapName => $counts) {
            $total = $instancesByMap[$mapName];
            $rates = collect($counts)->map(fn ($count, $agent) => [
                'agent' => $agent,
                'count' => $count,
                'pick_pct' => $total > 0 ? round($count / $total * 100, 1) : null,
            ])->sortByDesc('pick_pct')->values()->all();

            $pickRatesByMap[$mapName] = $rates;
        }

        return ['comps' => $byMap, 'pick_rates' => $pickRatesByMap];
    }

    /**
     * @return Builder<Matchs>
     */
    private function teamMatchesQuery($id): Builder
    {
        return Matchs::query()
            ->select([
                'matches.id',
                'matches.status',
                'matches.round_name',
                'matches.scheduled_at',
                'matches.team_a_score',
                'matches.team_b_score',
                'matches.team_a_id',
                'matches.team_b_id',
                'matches.tournament_id',
                'matches.phase_id',
            ])
            ->join('tournaments as t', 'matches.tournament_id', '=', 't.id')
            ->where('t.active', true)
            ->where(function ($query) use ($id) {
                $query->where('matches.team_a_id', $id)
                    ->orWhere('matches.team_b_id', $id);
            })
            ->with([
                'teamA:id,name',
                'teamB:id,name',
                'tournament:id,name',
                'tournamentPhase:id,name',
            ]);
    }

    private function formatTeamMatch(Matchs $match, $teamId): array
    {
        return MatchPresenter::format($match, $teamId);
    }
}
