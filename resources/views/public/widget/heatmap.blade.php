{{--
    GC-Stats — Player positions heatmap broadcast widget

    Standalone OBS-browser-source-friendly minimap heatmap: player position
    snapshots (kill/plant/defuse, see App\Models\GameMapRoundPlayerPosition)
    rendered as a density overlay on the map's square tactical minimap.
    Filtering (map/tournament/dates/side/team/player/event type) happens
    server-side via query string — see App\Http\Controllers\Public\WidgetController::heatmap()
    and App\Services\HeatmapService — this view just plots whatever the
    controller already filtered.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.widget')

@section('title', __('widgets.available.heatmap.name').' — '.ucfirst($mapName))

@section('content')
    <div class="w-screen h-screen flex items-center justify-center">
        <div id="heatmap-wrapper" class="relative overflow-hidden" style="width: min(100vw, 100vh); height: min(100vw, 100vh);">
            <img src="{{ asset('storage/'.$image) }}" alt="{{ ucfirst($mapName) }}"
                 class="absolute inset-0 w-full h-full object-contain select-none pointer-events-none">
            <canvas id="heatmap-canvas" class="absolute inset-0 w-full h-full" data-color="{{ $color }}"></canvas>
        </div>
    </div>

    <script type="application/json" id="heatmap-data">{!! json_encode($positions) !!}</script>

    @vite('resources/js/public/heatmap/index.js')
@endsection
