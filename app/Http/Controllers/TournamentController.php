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

namespace App\Http\Controllers;

use App\Helpers\CacheTtl;
use App\Models\GameMap;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\News;
use App\Models\PhaseQualification;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentPhase;
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
            'cash_prize' => $rule->cash_prize_amount !== null
                ? number_format((float) $rule->cash_prize_amount, 2).' '.$rule->cash_prize_currency
                : null,
            'label' => $rule->destination_type === 'phase'
                ? ($rule->destinationPhase?->tournament?->name.' — '.$rule->destinationPhase?->name)
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

        return view('tournament.index', array_merge([
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

        $cacheKey = "tournament_page_{$id}";
        $tag = "tournament_{$id}";

        $news = News::with(['author', 'publisher'])
            ->published()
            ->forLocale(app()->getLocale())
            ->whereHas('tournaments', fn ($q) => $q->where('tournaments.id', $id))
            ->latest('published_at')
            ->take(3)
            ->get()
            ->toArray();

        $tournamentMeta = Tournament::where('id', $id)->first(['status', 'active']);
        abort_unless($tournamentMeta, 404);

        if (! $tournamentMeta->active) {
            abort_unless(auth()->user()?->can('tournaments.view'), 404);
        }

        if ($tournamentMeta->active) {
            $cached = Cache::tags([$tag])->get($cacheKey);
            if ($cached) {
                $ttl = match ($cached['tournament']['status']) {
                    'finished' => 86400 * 7,
                    'upcoming' => 86400,
                    'live' => 60,
                    default => 3600,
                };

                return response()
                    ->view('tournament.show', array_merge($cached, ['news' => $news]))
                    ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
                    ->header('Vary', 'Accept-Language');
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
            if (! empty($teamIds)) {
                $teamsRaw = Team::with('currentLogo')
                    ->whereIn('id', $teamIds)
                    ->get(['id', 'name'])
                    ->keyBy('id')
                    ->map(fn ($x) => $x->toArray())
                    ->toArray();
            }

            $matchesByPhase = [];
            foreach ($matchesRaw as $m) {
                $pId = $m['phase_id'];

                $teamA = $teamsRaw[$m['team_a_id']] ?? null;
                $teamB = $teamsRaw[$m['team_b_id']] ?? null;

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
            ];
        };

        if (! $tournamentMeta->active) {
            $data = $buildData();

            return response()
                ->view('tournament.show', array_merge($data, ['news' => $news, 'inactive_access' => true]))
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        $data = Cache::tags([$tag])->remember($cacheKey, CacheTtl::forTournament($tournamentMeta->status), $buildData);

        $ttl = match ($data['tournament']['status']) {
            'finished' => 86400 * 7,
            'upcoming' => 86400,
            'live' => 60,
            default => 3600,
        };

        return response()
            ->view('tournament.show', array_merge($data, ['news' => $news]))
            ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
            ->header('Vary', 'Accept-Language');
    }

    public function matches(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.matches')) {
            return $redirect;
        }

        $page = $request->input('page', 1);
        $phaseId = $request->get('phase_id');
        $teamId = $request->get('team_id');
        $roundName = $request->get('round');
        $statusFilter = $request->get('status');

        $tag = "tournament_{$id}";

        $tournamentMeta = Tournament::where('id', $id)->first(['status', 'active']);
        abort_unless($tournamentMeta, 404);

        if (! $tournamentMeta->active) {
            abort_unless(auth()->user()?->can('tournaments.view'), 404);
        }

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

            $matches = new LengthAwarePaginator(
                $data['matches'],
                $data['meta']['total'],
                $data['meta']['per_page'],
                $data['meta']['current_page'],
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()
                ->view('tournament.matches', [
                    'tournament' => $data['tournament'],
                    'matches' => $matches,
                    'filters' => $filters,
                    'phaseId' => $phaseId,
                    'teamId' => $teamId,
                    'roundName' => $roundName,
                    'status' => $statusFilter,
                    'inactive_access' => true,
                ])
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        $filters = Cache::tags([$tag])->remember("tournament_matches_filters_{$id}", $ttl, $buildFilters);

        $filterKey = ($phaseId ?: 'all').'_'.($teamId ?: 'all').'_'.($roundName ?: 'all').'_'.($statusFilter ?: 'all');
        $cacheKey = "tournament_page_matches_{$id}_page_{$page}_{$filterKey}";

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
                ->view('tournament.matches', [
                    'tournament' => $cached['tournament'],
                    'matches' => $matches,
                    'filters' => $filters,
                    'phaseId' => $phaseId,
                    'teamId' => $teamId,
                    'roundName' => $roundName,
                    'status' => $statusFilter,
                ])
                ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
                ->header('Vary', 'Accept-Language');
        }

        $data = Cache::tags([$tag])->remember($cacheKey, $ttl, $buildPage);

        $matches = new LengthAwarePaginator(
            $data['matches'],
            $data['meta']['total'],
            $data['meta']['per_page'],
            $data['meta']['current_page'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()
            ->view('tournament.matches', [
                'tournament' => $data['tournament'],
                'matches' => $matches,
                'filters' => $filters,
                'phaseId' => $phaseId,
                'teamId' => $teamId,
                'roundName' => $roundName,
                'status' => $statusFilter,
            ])
            ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
            ->header('Vary', 'Accept-Language');
    }

    public function stats(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.stats')) {
            return $redirect;
        }

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

        if ($request->filled(['start_date', 'end_date'])) {
            $request->validate([
                'start_date' => ['date'],
                'end_date' => ['date'],
            ]);

            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $dateKey = 'range_'.$start->format('Ymd').'_'.$end->format('Ymd');
        } else {
            $dateKey = 'all_time';
        }

        $periodKey = ($phaseId ? "phase_{$phaseId}" : 'all_phases').'_'.$dateKey;
        $cacheKey = "tournament_stats_{$id}_{$periodKey}";
        $tag = "tournament_{$id}";

        $cached = Cache::tags([$tag])->get($cacheKey);
        if ($cached) {
            return response()
                ->view('tournament.stats', ['tournament' => $cached['tournament'], 'stats' => $cached['stats'], 'phases' => $parentPhases, 'selectedPhase' => $phaseId])
                ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
                ->header('Vary', 'Accept-Language');
        }

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id, $phaseIds, $start, $end) {
            $tournament = Tournament::findOrFail($id);

            $stats = GamePlayerStat::query()
                ->join('players', 'game_player_stats.player_id', '=', 'players.id')
                ->selectRaw('
                    game_player_stats.player_id,
                    players.handle as player_handle,
                    COUNT(*) as games_played,
                    GROUP_CONCAT(DISTINCT agent_name ORDER BY agent_name ASC SEPARATOR ",") as played_agents,
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
                ->where('game_player_stats.tournament_id', $id)
                ->when($phaseIds !== null, function ($query) use ($phaseIds) {
                    return $query->whereIn('game_player_stats.phase_id', $phaseIds);
                })
                ->when($start && $end, function ($query) use ($start, $end) {
                    return $query->whereBetween('game_player_stats.created_at', [$start, $end]);
                })
                ->groupBy('game_player_stats.player_id', 'players.handle')
                ->orderBy('avg_acs', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->played_agents = $item->played_agents ? explode(',', $item->played_agents) : [];

                    return $item;
                });

            return [
                'tournament' => $tournament->toArray(),
                'stats' => $stats->toArray(),
            ];
        });

        return response()
            ->view('tournament.stats', ['tournament' => $data['tournament'], 'stats' => $data['stats'], 'phases' => $parentPhases, 'selectedPhase' => $phaseId])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function maps(Request $request, $id, $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'tournaments.maps')) {
            return $redirect;
        }

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

        $cacheKey = 'tournament_maps_'.$id.'_'.($phaseId ? "phase_{$phaseId}" : 'all_phases');
        $tag = "tournament_{$id}";

        $data = Cache::tags([$tag])->remember($cacheKey, 3600, function () use ($id, $phaseIds) {
            $tournament = Tournament::findOrFail($id);

            $playedCounts = GameMap::where('tournament_id', $id)
                ->where('is_completed', true)
                ->whereNotNull('map_name')
                ->when($phaseIds !== null, fn ($q) => $q->whereIn('phase_id', $phaseIds))
                ->select('map_name', DB::raw('COUNT(*) as times_played'))
                ->groupBy('map_name')
                ->pluck('times_played', 'map_name');

            $winrates = DB::table('game_player_advanced_stats as apas')
                ->join('game_maps as gm', 'gm.id', '=', 'apas.game_map_id')
                ->where('gm.tournament_id', $id)
                ->where('gm.is_completed', true)
                ->whereNotNull('gm.map_name')
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

        return response()
            ->view('tournament.maps', ['tournament' => $data['tournament'], 'maps' => $data['maps'], 'phases' => $parentPhases, 'selectedPhase' => $phaseId])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
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
