<?php

/**
 * GC-Stats — Player page controller
 *
 * Renders the player profile page (bio, teams, stats, match history) and
 * caches the assembled data per player for one day, invalidated by PlayerObserver.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\News;
use App\Models\Player;
use App\Support\Achievements;
use App\Support\MatchPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PlayerController extends Controller
{
    /**
     * Redirects to the canonical slugged URL when the incoming slug is
     * missing or stale, so search engines only ever see one URL per player.
     */
    private function redirectToCanonicalSlug(int $id, ?string $slug, string $routeName)
    {
        $handle = Player::where('id', $id)->value('handle');
        abort_unless($handle !== null, 404);

        $canonical = Str::routeSlug($handle, $id);
        if ($slug !== $canonical) {
            return redirect()->route($routeName, [$id, $canonical], 301);
        }

        return null;
    }

    /**
     * Base query for a player's matches in active tournaments, joined to
     * their per-match team via game_player_stats, shared by the profile
     * page (index) and the full match history page (matches).
     */
    private function playerMatchesQuery(int $id)
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
                'gps.team_id as player_team_id',
            ])
            ->join('game_player_stats as gps', 'matches.id', '=', 'gps.match_id')
            ->join('tournaments as t', 'matches.tournament_id', '=', 't.id')
            ->where('gps.player_id', $id)
            ->where('t.active', true)
            ->with([
                'teamA:id,name',
                'teamB:id,name',
                'tournament:id,name',
                'tournamentPhase:id,name',
            ]);
    }

    /**
     * Rebuild a LengthAwarePaginator from cached page data + metadata
     * (Cache::remember only stores plain arrays, not paginator instances).
     */
    private function paginatorFromCache(array $items, array $meta, Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $items,
            $meta['total'],
            $meta['per_page'],
            $meta['current_page'],
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    public function index(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.show')) {
            return $redirect;
        }

        $cacheKey = "player_page_{$id}";
        $tag = "player_{$id}";

        $data = Cache::tags([$tag, 'players'])->remember($cacheKey, now()->addDay(), function () use ($id) {
            $player = Player::findOrFail($id);

            $mapTeam = fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'logo' => $team->logo,
                'pivot' => [
                    'role' => $team->pivot->role,
                    'joined_at' => $team->pivot->joined_at ? Carbon::parse($team->pivot->joined_at)->toDateString() : null,
                    'left_at' => $team->pivot->left_at ? Carbon::parse($team->pivot->left_at)->toDateString() : null,
                ],
            ];

            $currentTeamModel = $player->teams()
                ->select('teams.id', 'teams.name')
                ->withPivot('role', 'joined_at', 'left_at')
                ->wherePivotNull('left_at')
                ->first();

            $pastTeamsModels = $player->teams()
                ->select('teams.id', 'teams.name')
                ->withPivot('role', 'joined_at', 'left_at')
                ->wherePivotNotNull('left_at')
                ->limit(5)
                ->get();

            $currentTeam = $currentTeamModel ? $mapTeam($currentTeamModel) : null;
            $pastTeams = $pastTeamsModels->map($mapTeam)->all();
            $history = $currentTeam
                ? array_merge([$currentTeam], array_slice($pastTeams, 0, 4))
                : array_slice($pastTeams, 0, 5);

            $baseMatchQuery = $this->playerMatchesQuery($id);

            $upcomingMatchesRaw = (clone $baseMatchQuery)
                ->whereIn('matches.status', ['upcoming', 'live'])
                ->orderBy('matches.scheduled_at', 'asc')
                ->take(10)
                ->get();

            $pastMatchesRaw = (clone $baseMatchQuery)
                ->where('matches.status', 'finished')
                ->orderBy('matches.scheduled_at', 'desc')
                ->take(10)
                ->get();

            $processMatches = function ($matchesCollection) {
                $results = [];
                foreach ($matchesCollection as $match) {
                    $results[] = MatchPresenter::format($match, $match->player_team_id);
                }

                return $results;
            };

            return [
                'player' => $player->makeHidden(['teams'])->toArray(),
                'currentTeam' => $currentTeam,
                'pastTeams' => $pastTeams,
                'upcomingMatches' => $processMatches($upcomingMatchesRaw),
                'pastMatches' => $processMatches($pastMatchesRaw),
                'history' => $history,
                'achievements' => Achievements::forEntity($player),
            ];
        });

        $news = News::with(['author', 'publisher'])
            ->published()
            ->forLocale(app()->getLocale())
            ->whereHas('players', fn ($q) => $q->where('players.id', $id))
            ->latest('published_at')
            ->take(3)
            ->get()
            ->toArray();

        return response()
            ->view('player.index', array_merge($data, ['news' => $news]))
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function history(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.history')) {
            return $redirect;
        }

        $page = $request->integer('page', 1);
        $cacheKey = "player_history_{$id}_page_{$page}";
        $tag = "player_{$id}";

        $data = Cache::tags([$tag, 'players'])->remember($cacheKey, now()->addDay(), function () use ($id) {
            $player = Player::findOrFail($id);

            $paginated = $player->teams()
                ->select('teams.id', 'teams.name')
                ->withPivot('role', 'joined_at', 'left_at')
                ->wherePivotNotNull('left_at')
                ->paginate(10);

            $items = array_map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'logo' => $team->logo,
                'pivot' => [
                    'role' => $team->pivot->role,
                    'joined_at' => $team->pivot->joined_at,
                    'left_at' => $team->pivot->left_at,
                ],
            ], $paginated->items());

            return [
                'player' => $player->toArray(),
                'pastPlayersItems' => $items,
                'meta' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                ],
            ];
        });

        $pastTeams = $this->paginatorFromCache($data['pastPlayersItems'], $data['meta'], $request);

        return response()
            ->view('player.history', ['player' => $data['player'], 'pastTeams' => $pastTeams])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function matches(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.matches')) {
            return $redirect;
        }

        $page = $request->input('page', 1);
        $cacheKey = "player_page_matches_{$id}_page_{$page}";
        $tag = "player_{$id}";

        $cached = Cache::tags([$tag])->get($cacheKey);
        if ($cached) {
            $matches = $this->paginatorFromCache($cached['matches'], $cached['meta'], $request);

            return response()
                ->view('player.matches', ['player' => $cached['player'], 'matches' => $matches])
                ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
                ->header('Vary', 'Accept-Language');
        }

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id) {
            $player = Player::findOrFail($id)->toArray();

            $paginated = $this->playerMatchesQuery($id)
                ->orderBy('matches.scheduled_at', 'desc')
                ->paginate(10);

            $matchesArray = [];
            foreach ($paginated->items() as $match) {
                $matchesArray[] = MatchPresenter::format($match, $match->player_team_id);
            }

            return [
                'player' => $player,
                'matches' => $matchesArray,
                'meta' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                ],
            ];
        });

        $matches = $this->paginatorFromCache($data['matches'], $data['meta'], $request);

        return response()
            ->view('player.matches', ['player' => $data['player'], 'matches' => $matches])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function stats(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.stats')) {
            return $redirect;
        }

        $isAllTime = false;
        $start = null;
        $end = null;

        if ($request->filled(['start_date', 'end_date'])) {
            $request->validate([
                'start_date' => ['date'],
                'end_date' => ['date'],
            ]);

            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();

            $periodKey = 'range_'.$start->format('Ymd').'_'.$end->format('Ymd');
        } else {
            $days = $request->get('days', '0');

            if ($days === '0') {
                $isAllTime = true;
                $periodKey = 'all_time';
            } else {
                $start = now()->subDays($days)->startOfDay();
                $end = now()->endOfDay();
                $periodKey = "days_{$days}";
            }
        }

        $cacheKey = "player_stats_{$id}_{$periodKey}";
        $tag = "player_{$id}";

        $cached = Cache::tags([$tag])->get($cacheKey);
        if ($cached) {
            return response()
                ->view('player.stats', ['player' => $cached['player'], 'stats' => $cached['stats']])
                ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
                ->header('Vary', 'Accept-Language');
        }

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id, $start, $end, $isAllTime) {
            $player = Player::findOrFail($id);

            $stats = GamePlayerStat::query()
                ->selectRaw('
                agent_name,
                COUNT(*) as games_played,
                ROUND(AVG(acs), 2) as avg_acs,
                ROUND(AVG(kills), 2) as avg_kills,
                ROUND(AVG(deaths), 2) as avg_deaths,
                ROUND(AVG(assists), 2) as avg_assists,
                ROUND(AVG(adr), 2) as avg_adr,
                ROUND(AVG(first_kills), 2) as avg_first_kills,
                ROUND(AVG(first_deaths), 2) as avg_first_deaths,
                ROUND(AVG(kast_percentage), 2) as avg_kast,
                ROUND(AVG(headshot_percentage), 2) as avg_hs
            ')
                ->where('player_id', $id)
                ->when(! $isAllTime, function ($query) use ($start, $end) {
                    return $query->whereBetween('game_player_stats.created_at', [$start, $end]);
                })
                ->groupBy('agent_name')
                ->orderBy('avg_acs', 'desc')
                ->get();

            return [
                'player' => $player->toArray(),
                'stats' => $stats->toArray(),
            ];
        });

        return response()
            ->view('player.stats', ['player' => $data['player'], 'stats' => $data['stats']])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }
}
