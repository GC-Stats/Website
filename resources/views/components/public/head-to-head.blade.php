{{--
    GC-Stats — Head-to-head map comparison component

    Win-rate radar chart comparing two teams' own map pools (one axis per
    map, each team's own win_pct). A custom Chart.js plugin (see
    resources/js/public/head-to-head) draws a "wins/played" line per team,
    colored to match that team's polygon, directly under each map's axis
    label — so the raw sample size sits right on the chart instead of a
    separate list. Fed by App\Services\HeadToHeadService::compare(). Reused
    on the team/tournament maps pages, the match page, and the standalone
    OBS broadcast widget.

    `bare` drops the card chrome (background, border, header row) and lets
    the canvas fill its container — used by the broadcast widget page,
    which is meant to be dropped into an OBS Browser Source as just the
    chart, nothing else.

    When `$data['team_b']` is null (no second team picked yet), renders in
    solo mode: a single polygon for team A's own win rate per map, with
    just its "wins/played" record under each axis label instead of the
    two-team comparison. A second, low-opacity layer is drawn behind it
    showing times_played per map (normalized against the team's
    most-played map) so sample size reads visually, not just from the
    record label text.

    In solo mode the "win" polygon is plotted as wins/max_played (not
    wins/times_played) — the same denominator as the "played" layer — so
    a map's win shape can never poke out past its played shape: a win
    count can never exceed a play count. `win_pct` (the actual rate) is
    still shown as-is in comparison mode and kept separately as `winRate`
    for the tooltip/record label in solo mode.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['data', 'bare' => false])

@php
    $teamA = $data['team_a'];
    $teamB = $data['team_b'];
    $colorA = '#4ade80';
    $colorB = '#f87171';
    $id = 'h2h-'.substr(md5($teamA['id'].'-'.($teamB['id'] ?? 'solo').'-'.count($data['maps']).'-'.microtime()), 0, 10);
    $maxPlayed = $teamB ? 0 : collect($data['maps'])->max(fn ($m) => $m['team_a']['times_played'] ?? 0);

    $payload = [
        'labels' => collect($data['maps'])->pluck('map_name')->all(),
        'team_a' => ['name' => $teamA['short_name'] ?? $teamA['name'], 'color' => $colorA],
        'team_b' => $teamB ? ['name' => $teamB['short_name'] ?? $teamB['name'], 'color' => $colorB] : null,
        'win' => [
            'a' => $teamB
                ? collect($data['maps'])->map(fn ($m) => $m['team_a']['win_pct'] ?? 0)->all()
                : collect($data['maps'])->map(fn ($m) => $maxPlayed > 0 ? round(($m['team_a']['wins'] ?? 0) / $maxPlayed * 100, 1) : 0)->all(),
            'b' => $teamB ? collect($data['maps'])->map(fn ($m) => $m['team_b']['win_pct'] ?? 0)->all() : null,
        ],
        'winRate' => [
            'a' => collect($data['maps'])->map(fn ($m) => $m['team_a']['win_pct'] ?? 0)->all(),
            'b' => $teamB ? collect($data['maps'])->map(fn ($m) => $m['team_b']['win_pct'] ?? 0)->all() : null,
        ],
        'played' => $teamB ? null : collect($data['maps'])->map(fn ($m) => $maxPlayed > 0 ? round(($m['team_a']['times_played'] ?? 0) / $maxPlayed * 100, 1) : 0)->all(),
        'record' => [
            'a' => collect($data['maps'])->map(fn ($m) => ($m['team_a']['wins'] ?? 0).'/'.($m['team_a']['times_played'] ?? 0))->all(),
            'b' => $teamB ? collect($data['maps'])->map(fn ($m) => ($m['team_b']['wins'] ?? 0).'/'.($m['team_b']['times_played'] ?? 0))->all() : null,
        ],
    ];
@endphp

@if($bare)
    @if(count($data['maps']))
        <div class="relative w-full h-full" data-h2h-id="{{ $id }}">
            <canvas id="h2h-canvas-{{ $id }}"></canvas>
        </div>
        <script type="application/json" id="h2h-data-{{ $id }}">{!! json_encode($payload) !!}</script>
    @endif
@else
    <div class="bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden" data-h2h-id="{{ $id }}">
        <div class="p-4 md:p-5 border-b border-white/5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $colorA }}"></span>
                <img src="{{ $teamA['logo'] }}" alt="{{ $teamA['name'] }}" class="w-8 h-8 object-contain shrink-0">
                <span class="text-xs font-black uppercase tracking-wide text-white truncate">{{ $teamA['short_name'] ?? $teamA['name'] }}</span>
            </div>

            <span class="text-[9px] font-black uppercase tracking-widest text-gray-500 shrink-0">{{ __('head_to_head.toggle.win') }}</span>

            @if($teamB)
                <div class="flex items-center gap-3 min-w-0 justify-end">
                    <span class="text-xs font-black uppercase tracking-wide text-white truncate">{{ $teamB['short_name'] ?? $teamB['name'] }}</span>
                    <img src="{{ $teamB['logo'] }}" alt="{{ $teamB['name'] }}" class="w-8 h-8 object-contain shrink-0">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $colorB }}"></span>
                </div>
            @else
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 border border-white/30 bg-white/10"></span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-500">{{ __('head_to_head.toggle.played') }}</span>
                </div>
            @endif
        </div>

        <div class="p-4 md:p-5">
            @if(count($data['maps']))
                <div class="relative mx-auto" style="max-width: 480px; height: 400px;">
                    <canvas id="h2h-canvas-{{ $id }}"></canvas>
                </div>
                <script type="application/json" id="h2h-data-{{ $id }}">{!! json_encode($payload) !!}</script>
            @else
                <p class="text-center text-gray-500 py-6 text-xs">{{ $teamB ? __('head_to_head.no_data') : __('head_to_head.no_data_solo') }}</p>
            @endif
        </div>
    </div>
@endif
