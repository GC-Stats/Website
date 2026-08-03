<?php

/**
 * GC-Stats — Broadcast widget controller
 *
 * Renders standalone, chrome-less pages meant to be dropped into a
 * broadcast production tool (e.g. an OBS Browser Source) rather than
 * viewed in a normal browser tab — no nav/footer, transparent background.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\HeadToHeadService;
use App\Services\HeatmapService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WidgetController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'team_a' => ['nullable', 'integer'],
            'team_b' => ['nullable', 'integer'],
            'tournament_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'patch' => ['nullable', 'string'],
            'mappool' => ['nullable', 'string'],
            'map' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('valorant_minimaps')))],
            'side' => ['nullable', 'string', 'in:atk,def'],
            'team_id' => ['nullable', 'integer'],
            'player_id' => ['nullable', 'integer'],
            'event_type' => ['nullable', 'string'],
            'agent' => ['nullable', 'string', 'in:'.implode(',', config('valorant.agents'))],
            'color' => ['nullable', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'time_start' => ['nullable', 'integer', 'min:0'],
            'time_end' => ['nullable', 'integer', 'min:0', 'gte:time_start'],
            'time_reference' => ['nullable', 'string', 'in:round,plant'],
        ]);

        $teamA = $request->filled('team_a') ? Team::find((int) $request->team_a) : null;
        $teamB = $request->filled('team_b') ? Team::find((int) $request->team_b) : null;
        $tournament = $request->filled('tournament_id') ? Tournament::find((int) $request->tournament_id) : null;

        $generatedUrl = null;

        if ($teamA && $teamB) {
            $generatedUrl = route('widget.head-to-head', array_filter([
                'team_a' => $teamA->id,
                'team_b' => $teamB->id,
                'tournament_id' => $tournament?->id,
                'start_date' => $request->string('start_date')->toString() ?: null,
                'end_date' => $request->string('end_date')->toString() ?: null,
                'patch' => $request->string('patch')->toString() ?: null,
                'mappool' => $request->string('mappool')->toString() ?: null,
            ]));
        }

        $heatmapTeam = $request->filled('team_id') ? Team::find((int) $request->team_id) : null;
        $heatmapPlayer = $request->filled('player_id') ? Player::find((int) $request->player_id) : null;
        $heatmapGeneratedUrl = null;

        if ($request->filled('map')) {
            $heatmapGeneratedUrl = route('widget.heatmap', array_filter([
                'map' => $request->string('map')->toString(),
                'tournament_id' => $tournament?->id,
                'start_date' => $request->string('start_date')->toString() ?: null,
                'end_date' => $request->string('end_date')->toString() ?: null,
                'side' => $request->string('side')->toString() ?: null,
                'team_id' => $heatmapTeam?->id,
                'player_id' => $heatmapPlayer?->id,
                'event_type' => $request->string('event_type')->toString() ?: null,
                'agent' => $request->string('agent')->toString() ?: null,
                'color' => $request->string('color')->toString() ?: null,
                'time_start' => $request->filled('time_start') ? (int) $request->time_start : null,
                'time_end' => $request->filled('time_end') ? (int) $request->time_end : null,
                'time_reference' => $request->string('time_reference')->toString() ?: null,
            ]));
        }

        $previewMatch = Matchs::query()
            ->whereNotNull('team_a_id')->whereNotNull('team_b_id')
            ->where('status', 'finished')
            ->latest('scheduled_at')
            ->first();

        $widgets = [
            [
                'key' => 'head-to-head',
                'name' => __('widgets.available.head_to_head.name'),
                'description' => __('widgets.available.head_to_head.description'),
                'preview_url' => $previewMatch
                    ? route('widget.head-to-head', ['team_a' => $previewMatch->team_a_id, 'team_b' => $previewMatch->team_b_id])
                    : null,
            ],
            [
                'key' => 'heatmap',
                'name' => __('widgets.available.heatmap.name'),
                'description' => __('widgets.available.heatmap.description'),
                'preview_url' => route('widget.heatmap.preview'),
            ],
        ];

        return view('public.widget.index', [
            'widgets' => $widgets,
            'teamA' => $teamA,
            'teamB' => $teamB,
            'tournament' => $tournament,
            'generatedUrl' => $generatedUrl,
            'mapList' => array_keys(config('valorant_minimaps')),
            'selectedMap' => $request->string('map')->toString() ?: null,
            'selectedSide' => $request->string('side')->toString() ?: null,
            'heatmapTeam' => $heatmapTeam,
            'heatmapPlayer' => $heatmapPlayer,
            'selectedEventTypes' => $request->filled('event_type') ? explode(',', $request->string('event_type')->toString()) : ['kill', 'plant', 'defuse'],
            'agentList' => config('valorant.agents'),
            'selectedAgent' => $request->string('agent')->toString() ?: null,
            'selectedColor' => ltrim($request->string('color')->toString(), '#') ?: null,
            'selectedTimeStart' => $request->filled('time_start') ? (int) $request->time_start : null,
            'selectedTimeEnd' => $request->filled('time_end') ? (int) $request->time_end : null,
            'selectedTimeReference' => $request->string('time_reference')->toString() ?: 'round',
            'heatmapGeneratedUrl' => $heatmapGeneratedUrl,
        ]);
    }

    public function headToHead(Request $request)
    {
        $request->validate([
            'team_a' => ['required', 'integer'],
            'team_b' => ['required', 'integer'],
            'tournament_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'patch' => ['nullable', 'string'],
            'mappool' => ['nullable', 'string'],
        ]);

        $start = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $end = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;

        $headToHead = app(HeadToHeadService::class)->compare(
            (int) $request->team_a,
            (int) $request->team_b,
            $request->filled('tournament_id') ? (int) $request->tournament_id : null,
            $start,
            $end,
            $request->filled('patch') ? $request->string('patch')->toString() : null,
            $this->parseMapPool($request->string('mappool')->toString())
        );

        return response()
            ->view('public.widget.head-to-head', ['headToHead' => $headToHead])
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300');
    }

    public function heatmap(Request $request)
    {
        $request->validate([
            'map' => ['required', 'string', 'in:'.implode(',', array_keys(config('valorant_minimaps')))],
            'tournament_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'side' => ['nullable', 'string', 'in:atk,def'],
            'team_id' => ['nullable', 'integer'],
            'player_id' => ['nullable', 'integer'],
            'event_type' => ['nullable', 'string'],
            'agent' => ['nullable', 'string', 'in:'.implode(',', config('valorant.agents'))],
            'color' => ['nullable', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'time_start' => ['nullable', 'integer', 'min:0'],
            'time_end' => ['nullable', 'integer', 'min:0', 'gte:time_start'],
            'time_reference' => ['nullable', 'string', 'in:round,plant'],
        ]);

        $start = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $end = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;

        $mapName = strtolower($request->string('map')->toString());
        $tournamentId = $request->filled('tournament_id') ? (int) $request->tournament_id : null;
        $timeReference = $request->filled('time_reference') ? $request->string('time_reference')->toString() : 'round';

        $cacheKey = 'heatmap_positions_'.md5(serialize([
            $mapName,
            $tournamentId,
            $start?->toDateString(),
            $end?->toDateString(),
            $request->filled('side') ? $request->string('side')->toString() : null,
            $request->filled('team_id') ? (int) $request->team_id : null,
            $request->filled('player_id') ? (int) $request->player_id : null,
            $request->string('event_type')->toString(),
            $request->filled('agent') ? $request->string('agent')->toString() : null,
            $request->filled('time_start') ? (int) $request->time_start : null,
            $request->filled('time_end') ? (int) $request->time_end : null,
            $timeReference,
        ]));

        $tags = ['heatmap'];
        if ($tournamentId) {
            $tags[] = "tournament_{$tournamentId}";
        }

        $positions = Cache::tags($tags)->remember($cacheKey, 300, fn () => app(HeatmapService::class)->positions(
            $mapName,
            $tournamentId,
            $start,
            $end,
            $request->filled('side') ? $request->string('side')->toString() : null,
            $request->filled('team_id') ? (int) $request->team_id : null,
            $request->filled('player_id') ? (int) $request->player_id : null,
            $this->parseMapPool($request->string('event_type')->toString()),
            $request->filled('agent') ? $request->string('agent')->toString() : null,
            $request->filled('time_start') ? (int) $request->time_start : null,
            $request->filled('time_end') ? (int) $request->time_end : null,
            $timeReference,
        ));

        return response()
            ->view('public.widget.heatmap', [
                'mapName' => $mapName,
                'image' => config('valorant_minimaps.'.$mapName.'.image'),
                'positions' => $positions,
                'color' => ltrim($request->string('color')->toString(), '#') ?: null,
            ])
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300');
    }

    /**
     * The widgets directory's grid preview and the builder modal's "no
     * filters chosen yet" state both need a heatmap thumbnail that renders
     * instantly and never depends on the shape of live data — an unfiltered
     * HeatmapService query scans a map's entire position history and can
     * exhaust PHP's memory limit (see HeatmapService::positions()), which is
     * exactly what a directory-page thumbnail must never risk. This renders
     * the same view with fabricated positions instead of touching the DB.
     */
    public function heatmapPreview()
    {
        $mapName = 'ascent';

        $clusters = [
            ['x' => 0.30, 'y' => 0.24], // A site
            ['x' => 0.62, 'y' => 0.71], // B site
            ['x' => 0.46, 'y' => 0.48], // mid
        ];

        $eventTypes = ['kill', 'plant', 'defuse'];

        $positions = [];
        foreach (range(1, 260) as $i) {
            $cluster = $clusters[array_rand($clusters)];
            $positions[] = [
                'x' => min(1, max(0, $cluster['x'] + $this->gaussianJitter())),
                'y' => min(1, max(0, $cluster['y'] + $this->gaussianJitter())),
                'side' => $i % 2 === 0 ? 'atk' : 'def',
                'event_type' => $eventTypes[array_rand($eventTypes)],
                'team_id' => null,
                'player_id' => 0,
            ];
        }

        return response()
            ->view('public.widget.heatmap', [
                'mapName' => $mapName,
                'image' => config('valorant_minimaps.'.$mapName.'.image'),
                'positions' => $positions,
                'color' => null,
            ])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600');
    }

    /**
     * Box-Muller transform, scaled down to a small spread so points cluster
     * plausibly around a site/mid marker instead of scattering uniformly.
     */
    private function gaussianJitter(): float
    {
        $u1 = max(1e-9, mt_rand() / mt_getrandmax());
        $u2 = mt_rand() / mt_getrandmax();

        return sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2) * 0.06;
    }

    /**
     * Accepts `mappool=Ascent,Bind` or `mappool=[Ascent,Bind]` — a single
     * query string field is easier to paste into an OBS Browser Source URL
     * than array syntax (`mappool[]=Ascent&mappool[]=Bind`).
     */
    private function parseMapPool(string $raw): ?array
    {
        $raw = trim($raw, " \t\n\r\0\x0B[]");

        if ($raw === '') {
            return null;
        }

        $maps = array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($map) => $map !== ''));

        return $maps ?: null;
    }
}
