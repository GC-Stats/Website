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

use App\Services\HeadToHeadService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    public function headToHead(Request $request)
    {
        $request->validate([
            'team_a' => ['required', 'integer'],
            'team_b' => ['required', 'integer'],
            'tournament_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $start = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $end = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;

        $headToHead = app(HeadToHeadService::class)->compare(
            (int) $request->team_a,
            (int) $request->team_b,
            $request->filled('tournament_id') ? (int) $request->tournament_id : null,
            $start,
            $end
        );

        return response()
            ->view('public.widget.head-to-head', ['headToHead' => $headToHead])
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300');
    }
}
