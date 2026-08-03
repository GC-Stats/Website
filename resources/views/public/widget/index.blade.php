{{--
    GC-Stats — Widgets directory & link builder

    4-column grid of browser-source-friendly widgets (see
    App\Http\Controllers\Public\WidgetController and
    resources/views/public/widget/*): each card shows the widget's name and
    a square (1:1) live preview (a real iframe of the widget fed with recent
    data, not a static screenshot, so it never goes stale), and a "Configure"
    button that opens a modal with that widget's link builder (see
    partials/head-to-head-builder.blade.php) — same link this site's own
    "Share" buttons generate (resources/views/public/match.blade.php,
    partials/head-to-head-picker.blade.php), just reachable without
    visiting a match/team page first.

    The builder form inside a card's modal submits as a plain GET back to
    this page, so results/preview are server-rendered and shareable/
    bookmarkable on their own — see WidgetController::index() for
    $generatedUrl and how the matching card's modal re-opens itself on
    reload once team_a/team_b come back in the query string.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('widgets.title'))

@php
    $hasH2HBuilderQuery = request()->hasAny(['team_a', 'team_b', 'tournament_id', 'start_date', 'end_date', 'patch', 'mappool']);
    $hasHeatmapBuilderQuery = request()->hasAny(['map', 'side', 'team_id', 'player_id', 'event_type']);
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('widgets.title') }}
                </h1>
                <p class="text-sm text-gray-400 leading-relaxed italic mt-3 max-w-xl mx-auto">
                    {{ __('widgets.intro') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
                @foreach ($widgets as $widget)
                    @php
                        $widgetHasBuilderQuery = match ($widget['key']) {
                            'head-to-head' => $hasH2HBuilderQuery,
                            'heatmap' => $hasHeatmapBuilderQuery,
                            default => false,
                        };
                    @endphp
                    <div class="bg-bg-card border border-border-subtle rounded-sm overflow-hidden shadow-xl flex flex-col"
                         x-data="{ open: {{ $widgetHasBuilderQuery ? 'true' : 'false' }} }">
                        <div class="aspect-square bg-[#050505] border-b border-border-subtle relative overflow-hidden">
                            @if ($widget['preview_url'])
                                <iframe src="{{ $widget['preview_url'] }}"
                                        class="absolute inset-0 w-full h-full pointer-events-none"
                                        style="border: 0;" loading="lazy" tabindex="-1" aria-hidden="true"></iframe>
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-gray-600 text-[10px] font-bold uppercase tracking-widest px-4 text-center">
                                    {{ __('widgets.no_preview') }}
                                </div>
                            @endif
                        </div>

                        <div class="p-5 flex flex-col flex-1">
                            <p class="text-sm font-bold text-white uppercase tracking-wide">{{ $widget['name'] }}</p>
                            <p class="text-xs text-gray-400 leading-relaxed mt-2 flex-1">{{ $widget['description'] }}</p>

                            <button type="button" @click="open = true"
                                    class="mt-4 w-full py-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm bg-gc-yellow text-black hover:opacity-90 transition-opacity">
                                {{ __('widgets.configure') }}
                            </button>
                        </div>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak
                                 class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                                 @keydown.escape.window="open = false">
                                <div @click.away="open = false" role="dialog" aria-modal="true"
                                     class="w-full max-w-lg bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4 max-h-[85vh] overflow-y-auto">
                                    <div class="flex items-center justify-between gap-4">
                                        <h2 class="text-sm font-black uppercase tracking-widest text-white">{{ $widget['name'] }}</h2>
                                        <button type="button" @click="open = false" aria-label="{{ __('widgets.result.title') }}" class="text-gray-500 hover:text-white transition">
                                            <x-fas-xmark class="w-4 h-4" aria-hidden="true" />
                                        </button>
                                    </div>

                                    @if ($widget['key'] === 'head-to-head')
                                        @include('public.widget.partials.head-to-head-builder', [
                                            'teamA' => $teamA,
                                            'teamB' => $teamB,
                                            'tournament' => $tournament,
                                            'generatedUrl' => $generatedUrl,
                                        ])
                                    @elseif ($widget['key'] === 'heatmap')
                                        @include('public.widget.partials.heatmap-builder', [
                                            'mapList' => $mapList,
                                            'selectedMap' => $selectedMap,
                                            'selectedSide' => $selectedSide,
                                            'selectedEventTypes' => $selectedEventTypes,
                                            'tournament' => $tournament,
                                            'heatmapTeam' => $heatmapTeam,
                                            'heatmapPlayer' => $heatmapPlayer,
                                            'agentList' => $agentList,
                                            'selectedAgent' => $selectedAgent,
                                            'selectedColor' => $selectedColor,
                                            'selectedTimeStart' => $selectedTimeStart,
                                            'selectedTimeEnd' => $selectedTimeEnd,
                                            'heatmapGeneratedUrl' => $heatmapGeneratedUrl,
                                        ])
                                    @endif
                                </div>
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-gray-500 leading-relaxed text-center mt-4">
                {!! __('widgets.free_to_use_note', [
                    'terms_link' => '<a href="' . route('terms') . '" class="underline hover:text-gray-300 transition-colors">' . __('widgets.terms_of_use') . '</a>',
                ]) !!}
            </p>
        </section>
    </div>
@endsection
