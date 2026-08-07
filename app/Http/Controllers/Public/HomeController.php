<?php

/**
 * GC-Stats — Home page controller
 *
 * Renders the homepage, displaying live, upcoming and recently finished
 * matches alongside featured tournaments. Result is cached for 10 minutes.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Models\Matchs;
use App\Models\News;
use App\Models\Tournament;
use App\Support\CurrentTheme;
use App\Support\MatchDisplay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    protected function matchesQuery(): \Illuminate\Database\Eloquent\Builder
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
                'matches.match_order',
            ])
            ->join('tournaments', 'matches.tournament_id', '=', 'tournaments.id')
            ->where('tournaments.active', true)
            ->whereNotNull('matches.team_a_id')
            ->whereNotNull('matches.team_b_id')
            ->whereNotNull('matches.scheduled_at')
            ->whereDate('matches.scheduled_at', '!=', MatchDisplay::UNKNOWN_DATE)
            ->with([
                'teamA:id,name',
                'teamB:id,name',
                'tournament:id,name',
                'tournamentPhase:id,name,parent_id',
                'tournamentPhase.parent:id,name',
            ]);
    }

    protected function mapMatches(\Illuminate\Support\Collection $matches): array
    {
        return $matches
            ->map(fn ($m) => [
                'id' => $m->id,
                'status' => $m->status,
                'round_name' => $m->round_name,
                'scheduled_at' => $m->scheduled_at?->toDateTimeString(),
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
                'tournament' => ['name' => $m->tournament->name ?? ''],
                'phase' => ['name' => $m->tournamentPhase->parent->name ?? ($m->tournamentPhase->name ?? '')],
            ])
            ->all();
    }

    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'all');
        if (! in_array($statusFilter, ['all', 'upcoming', 'finished'], true)) {
            $statusFilter = 'all';
        }

        $matches = Cache::remember(
            "home_page_matches_{$statusFilter}_".CurrentTheme::get(),
            now()->addMinutes(5),
            function () use ($statusFilter) {
                $query = $this->matchesQuery();

                if ($statusFilter === 'all') {
                    // Recently finished (< 24h) matches are surfaced first, then live,
                    // then upcoming, then older finished matches.
                    $query->orderByRaw("CASE
                        WHEN matches.status = 'live' THEN 0
                        WHEN matches.status = 'finished' AND matches.scheduled_at >= NOW() - INTERVAL 1 DAY THEN 1
                        WHEN matches.status = 'upcoming' THEN 2
                        ELSE 3
                    END");
                } else {
                    $query->where('matches.status', $statusFilter);
                }

                $query->orderByRaw("CASE WHEN matches.status = 'upcoming' THEN UNIX_TIMESTAMP(matches.scheduled_at) ELSE -UNIX_TIMESTAMP(matches.scheduled_at) END")
                    ->orderBy('matches.match_order', 'asc');

                return $this->mapMatches($query->take(11)->get());
            }
        );

        $tournaments = Cache::remember('home_page_tournaments_'.CurrentTheme::get(), now()->addMinutes(5), function () {
            $statusOrder = ['live', 'upcoming', 'finished'];

            $tournaments = Tournament::query()
                ->select([
                    'id',
                    'name',
                    'status',
                    'region',
                    'start_date',
                    'end_date',
                    'category',
                ])
                ->where('active', true)
                ->orderByRaw("FIELD(status, 'live', 'upcoming', 'finished')")
                ->orderBy('end_date', 'desc')
                ->limit(22)
                ->get()
                ->groupBy('status');

            $orderedTournaments = [];
            foreach ($statusOrder as $key) {
                if ($tournaments->has($key)) {
                    $orderedTournaments[] = [
                        'label' => $key,
                        'items' => $tournaments->get($key)->toArray(),
                    ];
                }
            }

            return $orderedTournaments;
        });

        $locale = app()->getLocale();

        $newsData = Cache::remember("home_news_{$locale}", now()->addMinutes(10), function () use ($locale) {
            $featured = News::with(['author', 'publisher'])
                ->published()
                ->forLocale($locale)
                ->onHome()
                ->where('is_featured', true)
                ->latest('published_at')
                ->first()?->toArray();

            $newsItems = News::with(['author', 'publisher'])
                ->published()
                ->forLocale($locale)
                ->onHome()
                ->when($featured, fn ($q) => $q->where('id', '!=', $featured['id']))
                ->latest('published_at')
                ->take(15)
                ->get()
                ->toArray();

            return [
                'newsFeatured' => $featured,
                'newsItems' => $newsItems,
            ];
        });

        return view('public.index', [
            'matches' => $matches,
            'tournaments' => $tournaments,
            'newsFeatured' => $newsData['newsFeatured'],
            'newsItems' => $newsData['newsItems'],
            'statusFilter' => $statusFilter,
        ]);
    }
}
