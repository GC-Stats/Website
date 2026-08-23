<?php

/**
 * GC-Stats — Tournament controller
 *
 * Handles the tournament listing page (with region/category/year filters
 * and sorting) as well as individual tournament pages (overview, matches,
 * stats), with cache TTLs based on tournament status.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Helpers\AgentRoles;
use App\Helpers\CacheTtl;
use App\Models\GameMap;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\News;
use App\Models\PhaseQualification;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Services\HeadToHeadService;
use App\Services\UtilityStatsAggregator;
use App\Support\CurrentTheme;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    /**
     * Redirects to the canonical slugged URL when the incoming slug is
     * missing or stale, so search engines only ever see one URL per tournament.
     */
    private function redirectToCanonicalSlug($id, ?string $slug, string $routeName)
    {
        $name = Tournament::where('id', $id)->value('name');
        abort_unless($name !== null, 404);

        $canonical = Str::routeSlug($name, $id);
        if ($slug !== $canonical) {
            return redirect()->route($routeName, [$id, $canonical], 301);
        }

        return null;
    }

    /**
     * The public page's phase tabs only recognize root-level phase ids in
     * ?phase= (see tournament/show.blade.php's activePhase/setPhase), so a
     * qualification pointing at a nested phase (e.g. a swiss group under
     * "Group Stage") needs to link to its top-most ancestor for the tab
     * switch to actually land on it.
     */
    private function rootPhaseId(int $phaseId): ?int
    {
        $current = TournamentPhase::select('id', 'parent_id')->find($phaseId);

        while ($current?->parent_id) {
            $current = TournamentPhase::select('id', 'parent_id')->find($current->parent_id);
        }

        return $current?->id;
    }

    /**
     * Picks the root phase to show by default on the public tournament page:
     * the one currently in progress (now between its start_date and
     * end_date). Falls back to null (caller defaults to the first phase)
     * when no phase has dates or none is currently active, preserving the
     * previous behavior.
     */
    private function currentRootPhaseId(array $rootPhases): ?int
    {
        $now = Carbon::now();

        foreach ($rootPhases as $phase) {
            if (! $phase['start_date'] || ! $phase['end_date']) {
                continue;
            }

            if ($now->between(Carbon::parse($phase['start_date']), Carbon::parse($phase['end_date']))) {
                return $phase['id'];
            }
        }

        return null;
    }

    /**
     * Shared shape for a qualification rule as surfaced on the public page,
     * whether it's rank-based (swiss/round_robin) or match-outcome-based
     * (bracket) — used by the standings/leaderboard qualification badges
     * and the bracket's "half-match" qualified-team card.
     */
    private function mapQualificationRule(PhaseQualification $rule): array
    {
        $destTournament = $rule->destination_type === 'phase' ? $rule->destinationPhase?->tournament : null;

        return [
            'rank_from' => $rule->rank_from,
            'rank_to' => $rule->rank_to,
            'outcome' => $rule->outcome,
            'destination_type' => $rule->destination_type,
            'placement' => $rule->placement,
            'points' => $rule->points,
            'cash_prize' => $rule->formattedCashPrize(),
            'label' => $rule->destination_type === 'phase'
                ? (preg_replace('/^Game Changers\b/i', 'GC', $rule->destinationPhase?->tournament?->name ?? '').' — '.$rule->destinationPhase?->name)
                : ($rule->placement_label ?: '#'.$rule->placement),
            'url' => $destTournament
                ? route('tournaments.show', [$destTournament->id, Str::routeSlug($destTournament->name, $destTournament->id)])
                    .'?phase='.$this->rootPhaseId($rule->destination_phase_id)
                : null,
        ];
    }

    public function index(Request $request)
    {
        $inputs = array_filter($request->only(['region', 'category', 'year', 'sort', 'direction']), fn ($v) => $v !== null && $v !== '');

        $query = Tournament::query()->where('active', true)->withCount('teams');

        if (isset($inputs['region'])) {
            $query->where('region', $inputs['region']);
        }
        if (isset($inputs['category'])) {
            $query->where('category', $inputs['category']);
        }
        if (isset($inputs['year'])) {
            $query->whereYear('start_date', $inputs['year']);
        }

        $sort = $inputs['sort'] ?? 'date';
        $direction = $inputs['direction'] ?? ($sort === 'name' ? 'asc' : 'desc');

        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'desc';

        $query->orderBy($sort === 'name' ? 'name' : 'start_date', $direction);

        $tournaments = $query->paginate(12)->withQueryString();

        return view('public.tournament.index', array_merge([
            'tournaments' => $tournaments,
            'regions' => Tournament::distinct()->whereNotNull('region')->pluck('region'),
            'categories' => Tournament::distinct()->whereNotNull('category')->pluck('category'),
            'years' => Tournament::selectRaw('YEAR(start_date) as year')->distinct()->orderBy('year', 'desc')->pluck('year'),
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ], $inputs));
    }

    public function show($id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.show')) {
            return $redirect;
        }

        ['data' => $data, 'ttl' => $ttl, 'private' => $private] = $this->buildShowPayload($id);

        if ($private) {
            return response()
                ->view('public.tournament.show', $data)
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        return response()
            ->view('public.tournament.show', $data)
            ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the show page renders, as JSON. Shares the exact cache
     * entry the blade view reads/writes (same cache key/tag), so hitting
     * this endpoint never duplicates the underlying data build.
     */
    public function raw($id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.show')) {
            return $redirect;
        }

        ['data' => $data, 'ttl' => $ttl, 'private' => $private] = $this->buildShowPayload($id);

        if ($private) {
            return response()
                ->json($data)
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        return response()
            ->json($data)
            ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Builds the array the tournament show page (blade and /raw) renders:
     * the cached tournament/teams/matches/phases payload merged with the
     * per-request news list and default phase. Reads/writes the same cache
     * key as the previous behavior, so callers share one cache entry.
     */
    private function buildShowPayload($id): array
    {
        $tag = "tournament_{$id}";

        $news = News::with(['author', 'publisher'])
            ->published()
            ->forLocale(app()->getLocale())
            ->whereHas('tournaments', fn ($q) => $q->where('tournaments.id', $id))
            ->latest('published_at')
            ->take(3)
            ->get()
            ->toArray();

        $tournamentMeta = Tournament::where('id', $id)->first(['status', 'active', 'updated_at']);
        abort_unless($tournamentMeta, 404);

        if (! $tournamentMeta->active) {
            abort_unless(auth()->user()?->can('tournaments.view'), 404);
        }

        $cacheKey = "tournament_page_{$id}_{$tournamentMeta->updated_at->timestamp}_theme_".CurrentTheme::get();

        if ($tournamentMeta->active) {
            $cached = Cache::tags([$tag])->get($cacheKey);
            if ($cached) {
                $ttl = match ($cached['tournament']['status']) {
                    'finished' => 86400 * 7,
                    'upcoming' => 86400,
                    'live' => 60,
                    default => 3600,
                };

                return [
                    'data' => array_merge($cached, [
                        'news' => $news,
                        'default_phase_id' => $this->currentRootPhaseId($cached['root_phases']),
                    ]),
                    'ttl' => $ttl,
                    'private' => false,
                ];
            }
        }

        $buildData = function () use ($id) {
            $tournament = Tournament::with(['teams:id,name'])->findOrFail($id);

            $phasesRaw = DB::table('tournament_phases')
                ->where('tournament_id', $id)
                ->orderBy('order', 'asc')
                ->get()
                ->map(fn ($x) => (array) $x)
                ->toArray();

            $phaseIds = array_column($phasesRaw, 'id');

            $matchesRaw = [];
            if (! empty($phaseIds)) {
                $matchesRaw = DB::table('matches')
                    ->whereIn('phase_id', $phaseIds)
                    ->orderBy('round_number', 'asc')
                    ->orderBy('match_order', 'asc')
                    ->get()
                    ->map(fn ($x) => (array) $x)
                    ->toArray();
            }

            $matchIds = array_column($matchesRaw, 'id');

            $gameMapsByMatch = [];
            if (! empty($matchIds)) {
                foreach (DB::table('game_maps')->whereIn('match_id', $matchIds)->orderBy('order', 'asc')->get() as $gm) {
                    $gameMapsByMatch[$gm->match_id][] = [
                        'team_a_score' => $gm->team_a_score,
                        'team_b_score' => $gm->team_b_score,
                    ];
                }
            }

            $teamIds = array_unique(array_filter(array_merge(
                array_column($matchesRaw, 'team_a_id'),
                array_column($matchesRaw, 'team_b_id')
            )));

            $teamsRaw = [];
            $teamModels = collect();
            if (! empty($teamIds)) {
                $teamModels = Team::with('currentLogo', 'nameHistory', 'logos')
                    ->whereIn('id', $teamIds)
                    ->get(['id', 'name'])
                    ->keyBy('id');

                $teamsRaw = $teamModels->map(fn ($x) => $x->toArray())->toArray();
            }

            $matchesByPhase = [];
            foreach ($matchesRaw as $m) {
                $pId = $m['phase_id'];

                $teamA = $teamsRaw[$m['team_a_id']] ?? null;
                $teamB = $teamsRaw[$m['team_b_id']] ?? null;

                if ($teamA && ($teamAModel = $teamModels->get($m['team_a_id']))) {
                    $teamA['name'] = $teamAModel->nameAt($m['scheduled_at']);
                    $teamA['logo'] = $teamAModel->logoAt($m['scheduled_at'], CurrentTheme::get());
                }

                if ($teamB && ($teamBModel = $teamModels->get($m['team_b_id']))) {
                    $teamB['name'] = $teamBModel->nameAt($m['scheduled_at']);
                    $teamB['logo'] = $teamBModel->logoAt($m['scheduled_at'], CurrentTheme::get());
                }

                $matchesByPhase[$pId][] = [
                    'id' => $m['id'],
                    'round_name' => $m['round_name'] ?? null,
                    'phase_id' => $pId,
                    'team_a_id' => $m['team_a_id'],
                    'team_b_id' => $m['team_b_id'],
                    'team_a_score' => $m['team_a_score'],
                    'team_b_score' => $m['team_b_score'],
                    'round_number' => $m['round_number'],
                    'match_order' => $m['match_order'],
                    'status' => $m['status'] ?? null,
                    'scheduled_at' => $m['scheduled_at'],
                    'team_a' => $teamA,
                    'team_b' => $teamB,
                    'game_maps' => $gameMapsByMatch[$m['id']] ?? [],
                ];
            }

            $allPhases = [];
            foreach ($phasesRaw as $phase) {
                $phase['matches'] = $matchesByPhase[$phase['id']] ?? [];
                $allPhases[] = $phase;
            }

            $groupedPhases = [];
            foreach ($allPhases as $phase) {
                $groupedPhases[$phase['parent_id']][] = $phase;
            }

            // Rank-based qualification rules (swiss/round_robin), grouped by source phase.
            $qualificationsByPhase = PhaseQualification::whereIn('source_phase_id', $phaseIds)
                ->whereNull('source_match_id')
                ->with('destinationPhase.tournament:id,name')
                ->get()
                ->groupBy('source_phase_id')
                ->map(fn ($rules) => $rules->map(fn ($rule) => $this->mapQualificationRule($rule))->values()->toArray());

            // Match-outcome qualification rules (bracket phases), grouped by source match — shown as
            // a "half-match" qualified-team card in bracket-grid.blade.php.
            $qualificationsByMatch = PhaseQualification::whereIn('source_match_id', $matchIds)
                ->whereNotNull('source_match_id')
                ->with('destinationPhase.tournament:id,name')
                ->get()
                ->groupBy('source_match_id')
                ->map(fn ($rules) => $rules->map(fn ($rule) => $this->mapQualificationRule($rule))->values()->toArray());

            $formatPhase = function ($phase) use (&$formatPhase, $groupedPhases, $qualificationsByPhase, $qualificationsByMatch) {
                $data = [
                    'id' => $phase['id'],
                    'name' => $phase['name'],
                    'format' => $phase['format'],
                    'start_date' => $phase['start_date'],
                    'end_date' => $phase['end_date'],
                    'qualifications' => $qualificationsByPhase->get($phase['id'], []),
                ];

                $children = $groupedPhases[$phase['id']] ?? [];

                if (! empty($children)) {
                    $data['children'] = array_map(fn ($c) => $formatPhase($c), $children);
                } else {
                    $data['matches'] = array_map(function ($m) use ($qualificationsByMatch) {
                        return [
                            'id' => $m['id'],
                            'round_name' => $m['round_name'],
                            'phase_id' => $m['phase_id'],
                            'team_a_id' => $m['team_a_id'],
                            'team_b_id' => $m['team_b_id'],
                            'team_a_name' => $m['team_a']['name'] ?? null,
                            'team_b_name' => $m['team_b']['name'] ?? null,
                            'team_a_logo' => $m['team_a']['logo'] ?? null,
                            'team_b_logo' => $m['team_b']['logo'] ?? null,
                            'team_a_score' => $m['team_a_score'],
                            'team_b_score' => $m['team_b_score'],
                            'round_number' => $m['round_number'],
                            'match_order' => $m['match_order'],
                            'status' => $m['status'],
                            'scheduled_at' => $m['scheduled_at'] ? Carbon::parse($m['scheduled_at'])->toDateTimeString() : null,
                            'game_maps' => $m['game_maps'] ?? [],
                            'qualifications' => $qualificationsByMatch->get($m['id'], []),
                        ];
                    }, $phase['matches'] ?? []);
                }

                return $data;
            };

            $rootPhases = [];
            foreach ($allPhases as $phase) {
                if ($phase['parent_id'] === null) {
                    $rootPhases[] = $formatPhase($phase);
                }
            }

            $collectPhaseTeamIds = function ($phase) use (&$collectPhaseTeamIds) {
                $ids = [];

                foreach ($phase['matches'] ?? [] as $m) {
                    if (! empty($m['team_a_id'])) {
                        $ids[] = $m['team_a_id'];
                    }
                    if (! empty($m['team_b_id'])) {
                        $ids[] = $m['team_b_id'];
                    }
                }

                foreach ($phase['children'] ?? [] as $child) {
                    $ids = array_merge($ids, $collectPhaseTeamIds($child));
                }

                return $ids;
            };

            $allTournamentTeamIds = $tournament->teams->pluck('id')->all();

            $teamIdsByRootPhase = [];
            foreach ($rootPhases as $rp) {
                $ids = array_values(array_unique($collectPhaseTeamIds($rp)));

                $teamIdsByRootPhase[$rp['id']] = empty($ids) ? $allTournamentTeamIds : $ids;
            }

            $phaseIds = array_column($allPhases, 'id');
            $recentMatches = Matchs::whereIn('phase_id', $phaseIds)
                ->whereNotNull('team_a_id')
                ->whereNotNull('team_b_id')
                ->with(['teamA:id,name', 'teamB:id,name', 'tournamentPhase:id,name'])
                // Live first, then recently finished (< 24h), then upcoming, then older finished.
                ->orderByRaw("CASE
                    WHEN status = 'live' THEN 0
                    WHEN status = 'finished' AND scheduled_at >= NOW() - INTERVAL 1 DAY THEN 1
                    WHEN status = 'upcoming' THEN 2
                    ELSE 3
                END")
                ->orderByRaw("CASE WHEN status = 'upcoming' THEN UNIX_TIMESTAMP(scheduled_at) ELSE -UNIX_TIMESTAMP(scheduled_at) END")
                ->orderBy('match_order', 'asc')
                ->take(9)
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'status' => $m->status,
                    'scheduled_at' => $m->scheduled_at?->toDateTimeString(),
                    'team_a_name' => $m->teamA->name ?? null,
                    'team_b_name' => $m->teamB->name ?? null,
                    'team_a_score' => $m->team_a_score,
                    'team_b_score' => $m->team_b_score,
                    'team_a' => $m->teamA ? [
                        'id' => $m->teamA->id,
                        'name' => $m->teamA->name,
                        'logo' => $m->teamA->logo,
                    ] : null,
                    'team_b' => $m->teamB ? [
                        'id' => $m->teamB->id,
                        'name' => $m->teamB->name,
                        'logo' => $m->teamB->logo,
                    ] : null,
                    'tournament_phase' => ['name' => $m->tournamentPhase->name ?? ''],
                ]);

            return [
                'tournament' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
                    'start_date' => $tournament->start_date?->toDateTimeString(),
                    'end_date' => $tournament->end_date?->toDateTimeString(),
                    'region' => $tournament->region,
                    'prize_pool' => $tournament->prize_pool,
                    'description' => $tournament->description,
                    'location' => $tournament->location,
                    'logo' => $tournament->logo,
                    'liquipedia_link' => $tournament->liquipedia_link,
                ],
                'teams' => (function () use ($tournament) {
                    $teamIds = $tournament->teams->pluck('id');

                    $rostersByTeam = GamePlayerStat::query()
                        ->join('players', 'game_player_stats.player_id', '=', 'players.id')
                        ->where('game_player_stats.tournament_id', $tournament->id)
                        ->whereIn('game_player_stats.team_id', $teamIds)
                        ->select('players.id', 'players.handle', 'game_player_stats.team_id')
                        ->distinct()
                        ->orderBy('players.handle')
                        ->get()
                        ->groupBy('team_id');

                    return $tournament->teams->map(function ($team) use ($rostersByTeam) {
                        $roster = $rostersByTeam->get($team->id, collect());

                        return [
                            'id' => $team->id,
                            'name' => $team->name,
                            'logo' => $team->logo,
                            'roster' => $roster->map(fn ($p) => [
                                'id' => $p->id,
                                'handle' => $p->handle,
                            ])->toArray(),
                        ];
                    })->toArray();
                })(),
                'matches' => $recentMatches->toArray(),
                'root_phases' => $rootPhases,
                'team_ids_by_root_phase' => $teamIdsByRootPhase,
            ];
        };

        if (! $tournamentMeta->active) {
            $data = $buildData();

            return [
                'data' => array_merge($data, [
                    'news' => $news,
                    'inactive_access' => true,
                    'default_phase_id' => $this->currentRootPhaseId($data['root_phases']),
                ]),
                'ttl' => null,
                'private' => true,
            ];
        }

        $data = Cache::tags([$tag])->remember($cacheKey, CacheTtl::forTournament($tournamentMeta->status), $buildData);

        $ttl = match ($data['tournament']['status']) {
            'finished' => 86400 * 7,
            'upcoming' => 86400,
            'live' => 60,
            default => 3600,
        };

        return [
            'data' => array_merge($data, [
                'news' => $news,
                'default_phase_id' => $this->currentRootPhaseId($data['root_phases']),
            ]),
            'ttl' => $ttl,
            'private' => false,
        ];
    }

    public function matches(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.matches')) {
            return $redirect;
        }

        $payload = $this->buildMatchesPayload($request, $id);

        $matches = new LengthAwarePaginator(
            $payload['matches'],
            $payload['meta']['total'],
            $payload['meta']['per_page'],
            $payload['meta']['current_page'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $view = [
            'tournament' => $payload['tournament'],
            'matches' => $matches,
            'filters' => $payload['filters'],
            'phaseId' => $payload['phaseId'],
            'teamId' => $payload['teamId'],
            'roundName' => $payload['roundName'],
            'status' => $payload['status'],
        ];

        if ($payload['private']) {
            return response()
                ->view('public.tournament.matches', array_merge($view, ['inactive_access' => true]))
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        return response()
            ->view('public.tournament.matches', $view)
            ->header('Cache-Control', "public, max-age={$payload['ttl']}, s-maxage={$payload['ttl']}")
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the tournament matches page renders, as JSON (matches as
     * a plain paginated array instead of the blade's LengthAwarePaginator).
     * Shares the exact cache entries the blade view reads/writes, so
     * hitting this endpoint never duplicates the underlying data build.
     */
    public function matchesRaw(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.matches')) {
            return $redirect;
        }

        $payload = $this->buildMatchesPayload($request, $id);

        $body = [
            'tournament' => $payload['tournament'],
            'matches' => $payload['matches'],
            'meta' => $payload['meta'],
            'filters' => $payload['filters'],
            'phaseId' => $payload['phaseId'],
            'teamId' => $payload['teamId'],
            'roundName' => $payload['roundName'],
            'status' => $payload['status'],
        ];

        if ($payload['private']) {
            return response()
                ->json($body)
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        return response()
            ->json($body)
            ->header('Cache-Control', "public, max-age={$payload['ttl']}, s-maxage={$payload['ttl']}")
            ->header('Vary', 'Accept-Language');
    }

    private function buildMatchesPayload(Request $request, $id): array
    {
        $page = $request->input('page', 1);
        $phaseId = $request->get('phase_id');
        $teamId = $request->get('team_id');
        $roundName = $request->get('round');
        $statusFilter = $request->get('status');

        $tag = "tournament_{$id}";

        $tournamentMeta = Tournament::where('id', $id)->first(['status', 'active', 'updated_at']);
        abort_unless($tournamentMeta, 404);

        if (! $tournamentMeta->active) {
            abort_unless(auth()->user()?->can('tournaments.view'), 404);
        }

        $tournamentVersion = $tournamentMeta->updated_at->timestamp;
        $status = $tournamentMeta->status;
        $ttl = CacheTtl::forTournament($status);

        $buildFilters = function () use ($id) {
            $tournament = Tournament::findOrFail($id);

            $phases = TournamentPhase::where('tournament_id', $id)
                ->orderBy('order')
                ->get(['id', 'parent_id', 'name', 'format'])
                ->keyBy('id');

            $phaseOptions = $phases->map(function ($phase) use ($phases) {
                $name = $phase->name;
                $parent = $phase->parent_id ? $phases->get($phase->parent_id) : null;
                if ($parent) {
                    $name = $parent->name.' - '.$name;
                }

                return [
                    'id' => $phase->id,
                    'name' => $name,
                    'format' => $phase->format,
                ];
            })->values()->all();

            $teams = $tournament->teams()
                ->get(['teams.id', 'teams.name'])
                ->map(fn ($team) => ['id' => $team->id, 'name' => $team->name])
                ->sortBy('name')
                ->values()
                ->all();

            $rounds = Matchs::where('tournament_id', $id)
                ->whereIn('phase_id', $phases->whereIn('format', TournamentPhase::RANK_BASED_FORMATS)->pluck('id'))
                ->whereNotNull('round_name')
                ->select('phase_id', 'round_name')
                ->distinct()
                ->get()
                ->groupBy('phase_id')
                ->map(fn ($group) => $group->pluck('round_name')->unique()->values()->all())
                ->all();

            return [
                'phases' => $phaseOptions,
                'teams' => $teams,
                'rounds' => $rounds,
            ];
        };

        $buildPage = function () use ($id, $phaseId, $teamId, $roundName, $statusFilter) {
            $tournament = Tournament::findOrFail($id);
            $tournamentArray = $tournament->toArray();

            $phaseIds = null;
            if ($phaseId) {
                $allPhases = TournamentPhase::where('tournament_id', $id)->get(['id', 'parent_id']);
                $phaseIds = collect([(int) $phaseId]);
                $queue = [(int) $phaseId];
                while (! empty($queue)) {
                    $current = array_shift($queue);
                    $children = $allPhases->where('parent_id', $current)->pluck('id');
                    $phaseIds = $phaseIds->merge($children);
                    $queue = array_merge($queue, $children->all());
                }
            }

            $paginated = Matchs::query()
                ->select([
                    'id',
                    'round_name',
                    'scheduled_at',
                    'team_a_score',
                    'team_b_score',
                    'status',
                    'team_a_id',
                    'team_b_id',
                    'tournament_id',
                    'phase_id',
                    'match_order',
                ])
                ->where('tournament_id', $id)
                ->when($phaseIds, fn ($query) => $query->whereIn('phase_id', $phaseIds))
                ->when($teamId, fn ($query) => $query->where(function ($query) use ($teamId) {
                    $query->where('team_a_id', $teamId)->orWhere('team_b_id', $teamId);
                }))
                ->when($roundName, fn ($query) => $query->where('round_name', $roundName))
                ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
                ->with([
                    'teamA:id,name',
                    'teamB:id,name',
                    'tournamentPhase:id,name',
                ])
                // Live first, then recently finished (< 24h), then upcoming, then older finished.
                ->orderByRaw("CASE
                    WHEN status = 'live' THEN 0
                    WHEN status = 'finished' AND scheduled_at >= NOW() - INTERVAL 1 DAY THEN 1
                    WHEN status = 'upcoming' THEN 2
                    ELSE 3
                END")
                ->orderByRaw("CASE WHEN status = 'upcoming' THEN UNIX_TIMESTAMP(scheduled_at) ELSE -UNIX_TIMESTAMP(scheduled_at) END")
                ->orderBy('match_order', 'asc')
                ->paginate(10);

            $matchesArray = [];
            foreach ($paginated->items() as $match) {
                $matchesArray[] = [
                    'id' => $match->id,
                    'round_name' => $match->round_name,
                    'scheduled_at' => $match->scheduled_at?->toDateTimeString(),
                    'team_a_score' => (int) $match->team_a_score,
                    'team_b_score' => (int) $match->team_b_score,
                    'status' => $match->status,
                    'team_a' => $match->teamA ? [
                        'id' => $match->teamA->id,
                        'name' => $match->teamA->name,
                        'logo' => $match->teamA->logo,
                    ] : null,
                    'team_b' => $match->teamB ? [
                        'id' => $match->teamB->id,
                        'name' => $match->teamB->name,
                        'logo' => $match->teamB->logo,
                    ] : null,
                    'phase_name' => $match->tournamentPhase->name ?? '',
                ];
            }

            return [
                'tournament' => $tournamentArray,
                'matches' => $matchesArray,
                'meta' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                ],
            ];
        };

        if (! $tournamentMeta->active) {
            $filters = $buildFilters();
            $data = $buildPage();

            return array_merge($data, [
                'filters' => $filters,
                'phaseId' => $phaseId,
                'teamId' => $teamId,
                'roundName' => $roundName,
                'status' => $statusFilter,
                'ttl' => null,
                'private' => true,
            ]);
        }

        $filters = Cache::tags([$tag])->remember("tournament_matches_filters_{$id}_{$tournamentVersion}", $ttl, $buildFilters);

        $filterKey = ($phaseId ?: 'all').'_'.($teamId ?: 'all').'_'.($roundName ?: 'all').'_'.($statusFilter ?: 'all');
        $cacheKey = "tournament_page_matches_{$id}_page_{$page}_{$filterKey}_{$tournamentVersion}_theme_".CurrentTheme::get();

        $cached = Cache::tags([$tag])->get($cacheKey);
        if ($cached) {
            return array_merge($cached, [
                'filters' => $filters,
                'phaseId' => $phaseId,
                'teamId' => $teamId,
                'roundName' => $roundName,
                'status' => $statusFilter,
                'ttl' => $ttl,
                'private' => false,
            ]);
        }

        $data = Cache::tags([$tag])->remember($cacheKey, $ttl, $buildPage);

        return array_merge($data, [
            'filters' => $filters,
            'phaseId' => $phaseId,
            'teamId' => $teamId,
            'roundName' => $roundName,
            'status' => $statusFilter,
            'ttl' => $ttl,
            'private' => false,
        ]);
    }

    public function stats(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.stats')) {
            return $redirect;
        }

        $payload = $this->buildStatsPayload($request, $id);

        return response()
            ->view('public.tournament.stats', $payload)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the tournament stats page renders, as JSON. Shares the
     * exact cache entries the blade view reads/writes, so hitting this
     * endpoint never duplicates the underlying data build.
     */
    public function statsRaw(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.stats')) {
            return $redirect;
        }

        $payload = $this->buildStatsPayload($request, $id);

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    private function buildStatsPayload(Request $request, $id): array
    {
        $phaseId = $request->get('phase_id');

        $allPhases = TournamentPhase::where('tournament_id', $id)->get(['id', 'parent_id', 'name', 'order']);
        $parentPhases = $allPhases->whereNull('parent_id')->sortBy('order')->values();

        $phaseIds = null;
        if ($phaseId && $parentPhases->contains('id', (int) $phaseId)) {
            $phaseIds = collect([(int) $phaseId]);
            $queue = [(int) $phaseId];
            while (! empty($queue)) {
                $current = array_shift($queue);
                $children = $allPhases->where('parent_id', $current)->pluck('id');
                $phaseIds = $phaseIds->merge($children);
                $queue = array_merge($queue, $children->all());
            }
        } else {
            $phaseId = null;
        }

        $start = null;
        $end = null;

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $request->validate([
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
            ]);

            $start = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::createFromTimestamp(0);
            $end = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now();
            $dateKey = 'range_'.$start->format('Ymd').'_'.$end->format('Ymd');
        } else {
            $dateKey = 'all_time';
        }

        $agents = array_values(array_filter((array) $request->query('agents', [])));
        $roles = array_values(array_filter((array) $request->query('roles', [])));
        $maps = array_values(array_filter((array) $request->query('maps', [])));
        $roleSlugs = AgentRoles::slugsForRoles($roles);

        $tournamentUpdatedAt = Tournament::where('id', $id)->value('updated_at');
        abort_unless($tournamentUpdatedAt !== null, 404);

        $filterKey = md5(implode('|', [implode(',', $agents), implode(',', $roles), implode(',', $maps)]));
        $periodKey = ($phaseId ? "phase_{$phaseId}" : 'all_phases').'_'.$dateKey.'_'.$filterKey;
        $cacheKey = "tournament_stats_{$id}_{$periodKey}_".Carbon::parse($tournamentUpdatedAt)->timestamp.'_theme_'.CurrentTheme::get();
        $tag = "tournament_{$id}";

        $filterOptions = Cache::tags([$tag])->remember("tournament_stats_filters_{$id}_".Carbon::parse($tournamentUpdatedAt)->timestamp, 3600, function () use ($id) {
            return [
                'agents' => GamePlayerStat::where('tournament_id', $id)->distinct()->orderBy('agent_name')->pluck('agent_name')->all(),
                'maps' => GameMap::where('tournament_id', $id)->whereNotNull('map_name')->where('map_name', '!=', 'Unknown')->distinct()->orderBy('map_name')->pluck('map_name')->all(),
                'roles' => array_keys(config('agent_roles', [])),
            ];
        });

        $cached = Cache::tags([$tag])->get($cacheKey);
        if ($cached) {
            return ['tournament' => $cached['tournament'], 'stats' => $cached['stats'], 'insights' => $cached['insights'], 'weapons' => $cached['weapons'], 'phases' => $parentPhases, 'selectedPhase' => $phaseId, 'filterOptions' => $filterOptions, 'selectedAgents' => $agents, 'selectedRoles' => $roles, 'selectedMaps' => $maps];
        }

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id, $phaseIds, $start, $end, $agents, $maps, $roleSlugs) {
            $tournament = Tournament::findOrFail($id);

            $stats = GamePlayerStat::query()
                ->join('players', 'game_player_stats.player_id', '=', 'players.id')
                ->leftJoin('game_maps', 'game_maps.id', '=', 'game_player_stats.game_map_id')
                ->leftJoin('game_player_advanced_stats as gpas', function ($join) {
                    $join->on('gpas.game_map_id', '=', 'game_player_stats.game_map_id')
                        ->on('gpas.player_id', '=', 'game_player_stats.player_id');
                })
                ->selectRaw('
                    game_player_stats.player_id,
                    players.handle as player_handle,
                    COUNT(*) as games_played,
                    GROUP_CONCAT(DISTINCT game_player_stats.agent_name ORDER BY game_player_stats.agent_name ASC SEPARATOR ",") as played_agents,
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
                    SUM(COALESCE(gpas.clutch_1v1_total,0)+COALESCE(gpas.clutch_1v2_total,0)+COALESCE(gpas.clutch_1v3_total,0)+COALESCE(gpas.clutch_1v4_total,0)+COALESCE(gpas.clutch_1v5_total,0)) as clutch_attempts,
                    SUM(COALESCE(gpas.plants,0)) as total_plants,
                    SUM(COALESCE(gpas.defuses,0)) as total_defuses
                ')
                ->where('game_player_stats.tournament_id', $id)
                ->when($phaseIds !== null, function ($query) use ($phaseIds) {
                    return $query->whereIn('game_player_stats.phase_id', $phaseIds);
                })
                ->when($start && $end, function ($query) use ($start, $end) {
                    return $query->whereBetween('game_player_stats.created_at', [$start, $end]);
                })
                ->when($agents !== [], fn ($query) => $query->whereIn('game_player_stats.agent_name', $agents))
                ->when($maps !== [], fn ($query) => $query->whereIn('game_maps.map_name', $maps))
                ->when($roleSlugs !== [], fn ($query) => $query->whereIn(DB::raw('LOWER(REPLACE(game_player_stats.agent_name, "/", ""))'), $roleSlugs))
                ->groupBy('game_player_stats.player_id', 'players.handle')
                ->orderBy('avg_acs', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->played_agents = $item->played_agents ? explode(',', $item->played_agents) : [];
                    $item->played_maps = $item->played_maps ? explode(',', $item->played_maps) : [];

                    $item->total_clutches = (int) $item->clutches_won;
                    $item->avg_clutches = $item->games_played > 0 ? round($item->clutches_won / $item->games_played, 2) : 0.0;
                    $clutchRate = $item->clutch_attempts > 0 ? round($item->clutches_won / $item->clutch_attempts * 100, 1) : 0.0;
                    $item->total_clutch_rate = $clutchRate;
                    $item->avg_clutch_rate = $clutchRate;

                    $item->total_plants = (int) $item->total_plants;
                    $item->avg_plants = $item->games_played > 0 ? round($item->total_plants / $item->games_played, 2) : 0.0;
                    $item->total_defuses = (int) $item->total_defuses;
                    $item->avg_defuses = $item->games_played > 0 ? round($item->total_defuses / $item->games_played, 2) : 0.0;

                    return $item;
                });

            $gamesPlayed = $stats->pluck('games_played', 'player_id')->all();
            $killAggregator = app(UtilityStatsAggregator::class);
            $weapons = $killAggregator->weaponsFor($id, $phaseIds, $start, $end, null, $agents, $maps, $roleSlugs);
            $utility = $killAggregator->perPlayer($id, $phaseIds, $start, $end, $gamesPlayed, $weapons, $agents, $maps, $roleSlugs);

            $stats = $stats->map(function ($item) use ($utility) {
                foreach ($utility->get($item->player_id, []) as $key => $value) {
                    $item->{$key} = $value;
                }

                return $item;
            });

            return [
                'tournament' => $tournament->toArray(),
                'stats' => $stats->toArray(),
                'insights' => $this->buildStatsInsights($stats),
                'weapons' => $weapons,
            ];
        });

        return ['tournament' => $data['tournament'], 'stats' => $data['stats'], 'insights' => $data['insights'], 'weapons' => $data['weapons'], 'phases' => $parentPhases, 'selectedPhase' => $phaseId, 'filterOptions' => $filterOptions, 'selectedAgents' => $agents, 'selectedRoles' => $roles, 'selectedMaps' => $maps];
    }

    /**
     * Derive "leader" cards (top ACS/ADR/KAST/first kills/grenade kills/
     * clutches/Operator kills/Sheriff kills) from the already-computed
     * player stats list — no extra query. Mirrors buildMapInsights().
     * Weapon-based cards are skipped when the tournament has no kills with
     * that weapon (the dynamic weapon_<slug> field simply won't exist).
     */
    private function buildStatsInsights($stats): array
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
                'name' => $row->player_handle ?? null,
                'value' => $row->{$field} !== null ? $row->{$field}.$suffix : null,
            ];
        };

        return array_values(array_filter([
            $card('top_acs', 'avg_acs'),
            $card('top_adr', 'avg_adr'),
            $card('top_kast', 'avg_kast', '%'),
            $card('top_entries', 'avg_first_kills'),
            $card('top_utility', 'avg_grenade_kills'),
            $card('top_plants', 'total_plants'),
            $card('top_clutch_rate', 'total_clutch_rate', '%'),
            $card('top_operator', 'total_weapon_operator'),
            $card('top_sheriff', 'total_weapon_sheriff'),
        ]));
    }

    public function maps(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.maps')) {
            return $redirect;
        }

        $payload = $this->buildMapsPayload($request, $id);

        return response()
            ->view('public.tournament.maps', $payload)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Same payload the tournament maps page renders, as JSON. Shares the
     * exact cache entry the blade view reads/writes, so hitting this
     * endpoint never duplicates the underlying data build.
     */
    public function mapsRaw(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.maps')) {
            return $redirect;
        }

        $payload = $this->buildMapsPayload($request, $id);

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    private function buildMapsPayload(Request $request, $id): array
    {
        $phaseId = $request->get('phase_id');

        $allPhases = TournamentPhase::where('tournament_id', $id)->get(['id', 'parent_id', 'name', 'order']);
        $parentPhases = $allPhases->whereNull('parent_id')->sortBy('order')->values();

        $phaseIds = null;
        if ($phaseId && $parentPhases->contains('id', (int) $phaseId)) {
            $phaseIds = collect([(int) $phaseId]);
            $queue = [(int) $phaseId];
            while (! empty($queue)) {
                $current = array_shift($queue);
                $children = $allPhases->where('parent_id', $current)->pluck('id');
                $phaseIds = $phaseIds->merge($children);
                $queue = array_merge($queue, $children->all());
            }
        } else {
            $phaseId = null;
        }

        $tournamentUpdatedAt = Tournament::where('id', $id)->value('updated_at');
        abort_unless($tournamentUpdatedAt !== null, 404);

        $cacheKey = 'tournament_maps_'.$id.'_'.($phaseId ? "phase_{$phaseId}" : 'all_phases').'_'.Carbon::parse($tournamentUpdatedAt)->timestamp.'_theme_'.CurrentTheme::get();
        $tag = "tournament_{$id}";

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id, $phaseIds) {
            $tournament = Tournament::findOrFail($id);

            $playedCounts = GameMap::where('tournament_id', $id)
                ->where('is_completed', true)
                ->whereNotNull('map_name')
                ->where('map_name', '!=', 'Unknown')
                ->when($phaseIds !== null, fn ($q) => $q->whereIn('phase_id', $phaseIds))
                ->select('map_name', DB::raw('COUNT(*) as times_played'))
                ->groupBy('map_name')
                ->pluck('times_played', 'map_name');

            $winrates = DB::table('game_player_advanced_stats as apas')
                ->join('game_maps as gm', 'gm.id', '=', 'apas.game_map_id')
                ->where('gm.tournament_id', $id)
                ->where('gm.is_completed', true)
                ->whereNotNull('gm.map_name')
                ->where('gm.map_name', '!=', 'Unknown')
                ->when($phaseIds !== null, fn ($q) => $q->whereIn('gm.phase_id', $phaseIds))
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
                ->join('teams as t', 't.id', '=', 'gps.team_id')
                ->leftJoin('teams as ta', 'ta.id', '=', 'm.team_a_id')
                ->leftJoin('teams as tb', 'tb.id', '=', 'm.team_b_id')
                ->where('gm.tournament_id', $id)
                ->where('gm.is_completed', true)
                ->whereNotNull('gm.map_name')
                ->where('gm.map_name', '!=', 'Unknown')
                ->when($phaseIds !== null, fn ($q) => $q->whereIn('gm.phase_id', $phaseIds))
                ->select(
                    'gm.map_name', 'gm.id as game_map_id', 'gps.team_id', 't.name as team_name', 'gps.agent_name',
                    'gm.team_a_score', 'gm.team_b_score', 'm.team_a_id', 'm.team_b_id', 'm.id as match_id',
                    'm.scheduled_at', 'ta.name as team_a_name', 'tb.name as team_b_name'
                )
                ->get();

            ['comps' => $compsByMap, 'pick_rates' => $pickRatesByMap] = $this->buildMapComps($compRows);

            $maps = collect($playedCounts->keys())->map(function ($mapName) use ($playedCounts, $winrates, $compsByMap, $pickRatesByMap) {
                $wr = $winrates->get($mapName);
                $atkRounds = (int) ($wr->atk_rounds ?? 0);
                $atkWon = (int) ($wr->atk_rounds_won ?? 0);
                $defRounds = (int) ($wr->def_rounds ?? 0);
                $defWon = (int) ($wr->def_rounds_won ?? 0);

                return [
                    'map_name' => $mapName,
                    'times_played' => $playedCounts[$mapName],
                    'atk_win_pct' => $atkRounds > 0 ? round($atkWon / $atkRounds * 100, 1) : null,
                    'def_win_pct' => $defRounds > 0 ? round($defWon / $defRounds * 100, 1) : null,
                    'comps' => $compsByMap[$mapName] ?? [],
                    'pick_rates' => $pickRatesByMap[$mapName] ?? [],
                ];
            })->sortByDesc('times_played')->values();

            return [
                'tournament' => $tournament->toArray(),
                'maps' => $maps->toArray(),
            ];
        });

        $tournamentTeams = DB::table('tournament_teams')
            ->join('teams', 'teams.id', '=', 'tournament_teams.team_id')
            ->where('tournament_teams.tournament_id', $id)
            ->orderBy('teams.name')
            ->select('teams.id', 'teams.name')
            ->get();

        $headToHead = null;
        if ($request->filled(['h2h_team_a', 'h2h_team_b'])) {
            $validTeamIds = $tournamentTeams->pluck('id');

            if ($validTeamIds->contains((int) $request->h2h_team_a) && $validTeamIds->contains((int) $request->h2h_team_b)) {
                $headToHead = app(HeadToHeadService::class)->compare(
                    (int) $request->h2h_team_a,
                    (int) $request->h2h_team_b,
                    (int) $id
                );
            }
        }

        return [
            'tournament' => $data['tournament'],
            'maps' => $data['maps'],
            'phases' => $parentPhases,
            'selectedPhase' => $phaseId,
            'insights' => $this->buildMapInsights($data['maps']),
            'headToHead' => $headToHead,
            'tournamentTeams' => $tournamentTeams,
        ];
    }

    /**
     * Derive small "most/least played" / "best ATK/DEF winrate" cards from
     * the already-computed maps list — no extra query.
     */
    private function buildMapInsights(array $maps): array
    {
        if (empty($maps)) {
            return [];
        }

        $maps = collect($maps);
        $insights = [];

        $mostPlayed = $maps->sortByDesc('times_played')->first();
        $insights[] = ['label' => 'most_played', 'map_name' => $mostPlayed['map_name'], 'value' => __('tournament.maps.insights.times', ['count' => $mostPlayed['times_played']])];

        $leastPlayed = $maps->sortBy('times_played')->first();
        $insights[] = ['label' => 'least_played', 'map_name' => $leastPlayed['map_name'], 'value' => __('tournament.maps.insights.times', ['count' => $leastPlayed['times_played']])];

        $bestAtk = $maps->whereNotNull('atk_win_pct')->sortByDesc('atk_win_pct')->first();
        $insights[] = ['label' => 'best_atk', 'map_name' => $bestAtk['map_name'] ?? null, 'value' => $bestAtk ? $bestAtk['atk_win_pct'].'%' : null];

        $bestDef = $maps->whereNotNull('def_win_pct')->sortByDesc('def_win_pct')->first();
        $insights[] = ['label' => 'best_def', 'map_name' => $bestDef['map_name'] ?? null, 'value' => $bestDef ? $bestDef['def_win_pct'].'%' : null];

        return $insights;
    }

    /**
     * Group raw (map, game_map, team, agent) rows into 5-agent compositions
     * per team/map, keeping each comp's play count, win rate, and the list
     * of matches it was played in, keyed by map name. Also derives each
     * agent's pick rate per map (share of team/map drafts that included
     * them).
     */
    private function buildMapComps($rows): array
    {
        $comps = [];

        foreach ($rows->groupBy(fn ($r) => $r->game_map_id.'|'.$r->team_id) as $group) {
            $first = $group->first();
            $agents = $group->pluck('agent_name')->unique()->sort()->values()->all();

            if (count($agents) < 5) {
                continue;
            }

            $compKey = $first->map_name.'|'.$first->team_id.'|'.implode(',', $agents);
            $isTeamA = $first->team_id == $first->team_a_id;
            $ownScore = $isTeamA ? $first->team_a_score : $first->team_b_score;
            $oppScore = $isTeamA ? $first->team_b_score : $first->team_a_score;
            $opponentName = $isTeamA ? $first->team_b_name : $first->team_a_name;
            $won = ($ownScore ?? 0) > ($oppScore ?? 0);

            if (! isset($comps[$compKey])) {
                $comps[$compKey] = [
                    'map_name' => $first->map_name,
                    'team_id' => $first->team_id,
                    'team_name' => $first->team_name,
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
}
