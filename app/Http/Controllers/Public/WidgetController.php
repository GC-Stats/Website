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
use App\Models\Team;
use App\Models\Tournament;
use App\Services\HeadToHeadService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
        ];

        return view('public.widget.index', [
            'widgets' => $widgets,
            'teamA' => $teamA,
            'teamB' => $teamB,
            'tournament' => $tournament,
            'generatedUrl' => $generatedUrl,
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
