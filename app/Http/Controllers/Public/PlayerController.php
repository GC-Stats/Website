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

namespace App\Http\Controllers\Public;

use App\Helpers\AgentRoles;
use App\Models\GameMap;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\News;
use App\Models\Player;
use App\Services\UtilityStatsAggregator;
use App\Support\Achievements;
use App\Support\CurrentTheme;
use App\Support\MatchPresenter;
use App\Support\RosterMerger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            ->groupBy([
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
                'gps.team_id',
            ])
            ->with([
                'teamA:id,name',
                'teamA.nameHistory',
                'teamA.logos',
                'teamB:id,name',
                'teamB.nameHistory',
                'teamB.logos',
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

    /** Flattens a RosterMerger stint back into the ['id','name','logo','pivot' => [...]] shape the team views expect. */
    private function stintToTeamArray(array $stint): array
    {
        $team = $stint['model'];

        return [
            'id' => $team->id,
            'name' => $team->name,
            'logo' => $team->logo,
            'pivot' => [
                'role' => $stint['role'],
                'joined_at' => $stint['joined_at'] ? Carbon::parse($stint['joined_at'])->toDateString() : null,
                'left_at' => $stint['left_at'] ? Carbon::parse($stint['left_at'])->toDateString() : null,
                'inactive_since' => $stint['inactive_since'] ? Carbon::parse($stint['inactive_since'])->toDateString() : null,
            ],
        ];
    }

    public function index(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.show')) {
            return $redirect;
        }

        $data = $this->buildIndexPayload($id);

        return response()
            ->view('public.player.index', $data)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the player profile page renders, as JSON. Shares the
     * exact cache entry the blade view reads/writes (same cache key/tag),
     * so hitting this endpoint never duplicates the underlying data build.
     */
    public function raw(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.show')) {
            return $redirect;
        }

        $data = $this->buildIndexPayload($id);

        return response()
            ->json($data)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Builds the array the player profile page (blade and /raw) renders:
     * the cached teams/matches/achievements payload merged with the
     * per-request news list. Reads/writes the same cache key as the
     * previous behavior, so callers share one cache entry.
     */
    private function buildIndexPayload(int $id): array
    {
        $player = Player::findOrFail($id);
        $cacheKey = "player_page_{$id}_{$player->updated_at->timestamp}_theme_".CurrentTheme::get();
        $tag = "player_{$id}";

        $data = Cache::tags([$tag, 'players'])->remember($cacheKey, now()->addDay(), function () use ($id, $player) {
            // Showing all teams w/ a left_at, in theory, a player shoudn't have 2 teams in the same time
            // but because of missing data, a lot of player have multiples teams w/ left_at
            $teamStints = RosterMerger::merge(
                $player->teams()
                    ->select('teams.id', 'teams.name')
                    ->withPivot('role', 'joined_at', 'left_at')
                    ->get(),
                'team_id'
            );

            $currentTeams = $teamStints
                ->filter(fn ($stint) => $stint['left_at'] === null)
                ->take(5)
                ->map(fn ($stint) => $this->stintToTeamArray($stint))
                ->all();

            $pastTeams = $teamStints
                ->filter(fn ($stint) => $stint['left_at'] !== null)
                ->take(5)
                ->map(fn ($stint) => $this->stintToTeamArray($stint))
                ->all();

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
                'player' => $player->makeHidden(['teams', 'vlr_id', 'val_id', 'esports_val_id', 'discord_id'])->toArray(),
                'currentTeams' => $currentTeams,
                'pastTeams' => $pastTeams,
                'upcomingMatches' => $processMatches($upcomingMatchesRaw),
                'pastMatches' => $processMatches($pastMatchesRaw),
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

        return array_merge($data, ['news' => $news]);
    }

    public function history(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.history')) {
            return $redirect;
        }

        $data = $this->buildHistoryPayload($id, $request->integer('page', 1));
        $pastTeams = $this->paginatorFromCache($data['pastPlayersItems'], $data['meta'], $request);

        return response()
            ->view('public.player.history', ['player' => $data['player'], 'pastTeams' => $pastTeams])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the player history page renders, as JSON (pastPlayersItems
     * as a plain paginated array instead of the blade's LengthAwarePaginator).
     * Shares the exact cache entry the blade view reads/writes.
     */
    public function historyRaw(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.history')) {
            return $redirect;
        }

        $data = $this->buildHistoryPayload($id, $request->integer('page', 1));

        return response()
            ->json($data)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    private function buildHistoryPayload(int $id, int $page): array
    {
        $player = Player::findOrFail($id);
        $cacheKey = "player_history_{$id}_page_{$page}_{$player->updated_at->timestamp}_theme_".CurrentTheme::get();
        $tag = "player_{$id}";

        return Cache::tags([$tag, 'players'])->remember($cacheKey, now()->addDay(), function () use ($player, $page) {
            $pastStints = RosterMerger::merge(
                $player->teams()
                    ->select('teams.id', 'teams.name')
                    ->withPivot('role', 'joined_at', 'left_at')
                    ->get(),
                'team_id'
            )
                ->filter(fn ($stint) => $stint['left_at'] !== null)
                ->values();

            $perPage = 10;
            $page = max(1, (int) $page);
            $items = $pastStints->forPage($page, $perPage)->map(fn ($stint) => $this->stintToTeamArray($stint))->values()->all();

            return [
                'player' => $player->makeHidden(['vlr_id', 'val_id', 'esports_val_id', 'discord_id'])->toArray(),
                'pastPlayersItems' => $items,
                'meta' => [
                    'total' => $pastStints->count(),
                    'per_page' => $perPage,
                    'current_page' => $page,
                ],
            ];
        });
    }

    public function matches(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.matches')) {
            return $redirect;
        }

        $data = $this->buildMatchesPayload($id, $request->input('page', 1));
        $matches = $this->paginatorFromCache($data['matches'], $data['meta'], $request);

        return response()
            ->view('public.player.matches', ['player' => $data['player'], 'matches' => $matches])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the player matches page renders, as JSON (matches as a
     * plain paginated array instead of the blade's LengthAwarePaginator).
     * Shares the exact cache entry the blade view reads/writes.
     */
    public function matchesRaw(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.matches')) {
            return $redirect;
        }

        $data = $this->buildMatchesPayload($id, $request->input('page', 1));

        return response()
            ->json($data)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    private function buildMatchesPayload(int $id, $page): array
    {
        $playerUpdatedAt = Player::where('id', $id)->value('updated_at');
        abort_unless($playerUpdatedAt !== null, 404);

        $cacheKey = "player_page_matches_{$id}_page_{$page}_".Carbon::parse($playerUpdatedAt)->timestamp.'_theme_'.CurrentTheme::get();
        $tag = "player_{$id}";

        return Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id) {
            $player = Player::findOrFail($id)->makeHidden(['vlr_id', 'val_id', 'esports_val_id', 'discord_id'])->toArray();

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
    }

    public function stats(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.stats')) {
            return $redirect;
        }

        $payload = $this->buildStatsPayload($request, $id);

        return response()
            ->view('public.player.stats', $payload)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the player stats page renders, as JSON. Shares the exact
     * cache entries the blade view reads/writes.
     */
    public function statsRaw(Request $request, int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'players.stats')) {
            return $redirect;
        }

        $payload = $this->buildStatsPayload($request, $id);

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    private function buildStatsPayload(Request $request, int $id): array
    {
        $isAllTime = false;
        $start = null;
        $end = null;

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $request->validate([
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
            ]);

            $start = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::createFromTimestamp(0);
            $end = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now();

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

        $agents = array_values(array_filter((array) $request->query('agents', [])));
        $roles = array_values(array_filter((array) $request->query('roles', [])));
        $maps = array_values(array_filter((array) $request->query('maps', [])));
        $roleSlugs = AgentRoles::slugsForRoles($roles);

        $playerUpdatedAt = Player::where('id', $id)->value('updated_at');
        abort_unless($playerUpdatedAt !== null, 404);

        $filterKey = md5(implode('|', [implode(',', $agents), implode(',', $roles), implode(',', $maps)]));
        $cacheKey = "player_stats_{$id}_{$periodKey}_{$filterKey}_".Carbon::parse($playerUpdatedAt)->timestamp;
        $tag = "player_{$id}";

        $filterOptions = Cache::tags([$tag])->remember("player_stats_filters_{$id}_".Carbon::parse($playerUpdatedAt)->timestamp, 3600, function () use ($id) {
            return [
                'agents' => GamePlayerStat::where('player_id', $id)->distinct()->orderBy('agent_name')->pluck('agent_name')->all(),
                'maps' => GameMap::join('game_player_stats', 'game_player_stats.game_map_id', '=', 'game_maps.id')
                    ->where('game_player_stats.player_id', $id)
                    ->whereNotNull('game_maps.map_name')
                    ->where('game_maps.map_name', '!=', 'Unknown')
                    ->distinct()->orderBy('game_maps.map_name')->pluck('game_maps.map_name')->all(),
                'roles' => array_keys(config('agent_roles', [])),
            ];
        });

        $cached = Cache::tags([$tag])->get($cacheKey);
        if ($cached) {
            return ['player' => $cached['player'], 'stats' => $cached['stats'], 'insights' => $cached['insights'], 'weapons' => $cached['weapons'], 'filterOptions' => $filterOptions, 'selectedAgents' => $agents, 'selectedRoles' => $roles, 'selectedMaps' => $maps];
        }

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id, $start, $end, $isAllTime, $agents, $maps, $roleSlugs) {
            $player = Player::findOrFail($id);

            $stats = GamePlayerStat::query()
                ->leftJoin('game_maps', 'game_maps.id', '=', 'game_player_stats.game_map_id')
                ->leftJoin('game_player_advanced_stats as gpas', function ($join) {
                    $join->on('gpas.game_map_id', '=', 'game_player_stats.game_map_id')
                        ->on('gpas.player_id', '=', 'game_player_stats.player_id');
                })
                ->selectRaw('
                game_player_stats.agent_name,
                COUNT(*) as games_played,
                GROUP_CONCAT(DISTINCT CASE WHEN game_maps.map_name IS NOT NULL AND game_maps.map_name != "Unknown" THEN game_maps.map_name END ORDER BY game_maps.map_name ASC SEPARATOR ",") as played_maps,
                ROUND(AVG(acs), 2) as avg_acs,
                SUM(acs) as total_acs,
                ROUND(AVG(kills), 2) as avg_kills,
                SUM(kills) as total_kills,
                ROUND(AVG(deaths), 2) as avg_deaths,
                SUM(deaths) as total_deaths,
                ROUND(AVG(assists), 2) as avg_assists,
                SUM(assists) as total_assists,
                ROUND(AVG(adr), 2) as avg_adr,
                SUM(adr) as total_adr,
                ROUND(AVG(first_kills), 2) as avg_first_kills,
                SUM(first_kills) as total_first_kills,
                ROUND(AVG(first_deaths), 2) as avg_first_deaths,
                SUM(first_deaths) as total_first_deaths,
                ROUND(AVG(kast_percentage), 2) as avg_kast,
                SUM(kast_percentage) as total_kast,
                ROUND(AVG(headshot_percentage), 2) as avg_hs,
                SUM(headshot_percentage) as total_hs,
                SUM(COALESCE(gpas.clutch_1v1_won,0)+COALESCE(gpas.clutch_1v2_won,0)+COALESCE(gpas.clutch_1v3_won,0)+COALESCE(gpas.clutch_1v4_won,0)+COALESCE(gpas.clutch_1v5_won,0)) as clutches_won,
                SUM(COALESCE(gpas.clutch_1v1_total,0)+COALESCE(gpas.clutch_1v2_total,0)+COALESCE(gpas.clutch_1v3_total,0)+COALESCE(gpas.clutch_1v4_total,0)+COALESCE(gpas.clutch_1v5_total,0)) as clutch_attempts
            ')
                ->where('game_player_stats.player_id', $id)
                ->when(! $isAllTime, function ($query) use ($start, $end) {
                    return $query->whereBetween('game_player_stats.created_at', [$start, $end]);
                })
                ->when($agents !== [], fn ($query) => $query->whereIn('game_player_stats.agent_name', $agents))
                ->when($maps !== [], fn ($query) => $query->whereIn('game_maps.map_name', $maps))
                ->when($roleSlugs !== [], fn ($query) => $query->whereIn(DB::raw('LOWER(REPLACE(game_player_stats.agent_name, "/", ""))'), $roleSlugs))
                ->groupBy('game_player_stats.agent_name')
                ->orderBy('avg_acs', 'desc')
                ->get();

            $gamesPlayedByAgent = $stats->pluck('games_played', 'agent_name')->all();
            $killAggregator = app(UtilityStatsAggregator::class);
            $scopedStart = $isAllTime ? null : $start;
            $scopedEnd = $isAllTime ? null : $end;
            $weapons = $killAggregator->weaponsFor(null, null, $scopedStart, $scopedEnd, $id, [], $maps, $roleSlugs);
            $utility = $killAggregator->perAgent($id, $scopedStart, $scopedEnd, $gamesPlayedByAgent, $weapons, $maps, $roleSlugs);

            $stats = $stats->map(function ($item) use ($utility) {
                $item->played_maps = $item->played_maps ? explode(',', $item->played_maps) : [];

                $item->total_clutches = (int) $item->clutches_won;
                $item->avg_clutches = $item->games_played > 0 ? round($item->clutches_won / $item->games_played, 2) : 0.0;
                $clutchRate = $item->clutch_attempts > 0 ? round($item->clutches_won / $item->clutch_attempts * 100, 1) : 0.0;
                $item->total_clutch_rate = $clutchRate;
                $item->avg_clutch_rate = $clutchRate;

                foreach ($utility->get($item->agent_name, []) as $key => $value) {
                    $item->{$key} = $value;
                }

                $item->ability_names = [
                    'Ability1' => AgentRoles::abilityNameFor($item->agent_name, 'Ability1'),
                    'Ability2' => AgentRoles::abilityNameFor($item->agent_name, 'Ability2'),
                    'GrenadeAbility' => AgentRoles::abilityNameFor($item->agent_name, 'GrenadeAbility'),
                    'Ultimate' => AgentRoles::abilityNameFor($item->agent_name, 'Ultimate'),
                ];

                return $item;
            });

            return [
                'player' => $player->makeHidden(['vlr_id', 'val_id', 'esports_val_id', 'discord_id'])->toArray(),
                'stats' => $stats->toArray(),
                'insights' => $this->buildAgentStatsInsights($stats),
                'weapons' => $weapons,
            ];
        });

        return ['player' => $data['player'], 'stats' => $data['stats'], 'insights' => $data['insights'], 'weapons' => $data['weapons'], 'filterOptions' => $filterOptions, 'selectedAgents' => $agents, 'selectedRoles' => $roles, 'selectedMaps' => $maps];
    }

    /**
     * Derive "best agent" leader cards (ACS/ADR/KAST/first kills/grenade
     * kills/clutches/Operator kills/Sheriff kills) from the already-computed
     * per-agent stats list — no extra query. Mirrors
     * TournamentController::buildStatsInsights(). Weapon-based cards are
     * skipped when the player has no kills with that weapon in scope.
     */
    private function buildAgentStatsInsights($stats): array
    {
        $stats = collect($stats)->filter(fn ($row) => ($row->games_played ?? 0) > 0);

        if ($stats->isEmpty()) {
            return [];
        }

        $availableFields = array_keys($stats->first()->getAttributes());
        $leader = fn (string $field) => $stats->sortByDesc($field)->first();

        $card = function (string $label, string $field, string $suffix = '') use ($leader, $availableFields) {
            if (! in_array($field, $availableFields, true)) {
                return null;
            }

            $row = $leader($field);

            return [
                'label' => $label,
                'name' => $row->agent_name ?? null,
                'value' => $row->{$field} !== null ? $row->{$field}.$suffix : null,
            ];
        };

        return array_values(array_filter([
            $card('top_acs', 'avg_acs'),
            $card('top_adr', 'avg_adr'),
            $card('top_kast', 'avg_kast', '%'),
            $card('top_entries', 'avg_first_kills'),
            $card('top_utility', 'avg_grenade_kills'),
            $card('top_clutch_rate', 'total_clutch_rate', '%'),
            $card('top_operator', 'total_weapon_operator'),
            $card('top_sheriff', 'total_weapon_sheriff'),
        ]));
    }
}
