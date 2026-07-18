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

namespace App\Http\Controllers;

use App\Models\Matchs;
use App\Models\News;
use App\Models\Tournament;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $data = Cache::remember('home_page', now()->addMinutes(10), function () {
            $statusOrder = ['live', 'upcoming', 'finished'];

            $matches = Matchs::query()
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
                ->with([
                    'teamA:id,name',
                    'teamB:id,name',
                    'tournament:id,name',
                    'tournamentPhase:id,name,parent_id',
                    'tournamentPhase.parent:id,name',
                ])
                // Recently finished (< 24h) matches are surfaced first, then live,
                // then upcoming, then older finished matches.
                ->orderByRaw("CASE
                    WHEN matches.status = 'live' THEN 0
                    WHEN matches.status = 'finished' AND matches.scheduled_at >= NOW() - INTERVAL 1 DAY THEN 1
                    WHEN matches.status = 'upcoming' THEN 2
                    ELSE 3
                END")
                ->orderByRaw("CASE WHEN matches.status = 'upcoming' THEN UNIX_TIMESTAMP(matches.scheduled_at) ELSE -UNIX_TIMESTAMP(matches.scheduled_at) END")
                ->orderBy('matches.match_order', 'asc')
                ->take(11)
                ->get()
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

            return [
                'matches' => $matches,
                'tournaments' => $orderedTournaments,
            ];
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

        return view('index', [
            'matches' => $data['matches'],
            'tournaments' => $data['tournaments'],
            'newsFeatured' => $newsData['newsFeatured'],
            'newsItems' => $newsData['newsItems'],
        ]);
    }
}
