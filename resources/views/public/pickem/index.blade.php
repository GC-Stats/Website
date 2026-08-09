{{--
    GC-Stats — Pick'em dashboard

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('pickem.title.index'))

@section('content')
    <div class="max-w-5xl mx-auto flex flex-col gap-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black uppercase italic text-white">{{ __('pickem.title.index') }}</h1>
                <p class="text-sm text-gray-400 mt-1">{{ __('pickem.index.intro') }}</p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('pickem.leaderboard') }}"
                   class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:border-gc-yellow/40 transition">
                    {{ __('pickem.index.view_leaderboard') }}
                </a>
                <a href="{{ route('pickem.groups.index') }}"
                   class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:border-gc-yellow/40 transition">
                    {{ __('pickem.index.view_groups') }}
                </a>
            </div>
        </div>

        @auth
            <div class="grid grid-cols-2 gap-4 max-w-md">
                <div class="bg-bg-card border border-border-subtle rounded-lg p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('pickem.index.your_points') }}</div>
                    <div class="text-2xl font-black text-gc-yellow mt-1">{{ $totalPoints }}</div>
                </div>
                <div class="bg-bg-card border border-border-subtle rounded-lg p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('pickem.index.your_rank') }}</div>
                    <div class="text-2xl font-black text-white mt-1">{{ $globalRank ? '#'.$globalRank : __('pickem.index.no_rank_yet') }}</div>
                </div>
            </div>
        @endauth

        @php
            $sections = [
                'live' => $livePhases,
                'upcoming' => $upcomingPhases,
                'finished' => $finishedPhases,
            ];
            $isEmpty = $livePhases->isEmpty() && $upcomingPhases->isEmpty() && $finishedPhases->isEmpty();
        @endphp

        @if ($isEmpty)
            <p class="text-sm text-gray-500 text-center py-12">{{ __('pickem.index.empty') }}</p>
        @else
            @foreach ($sections as $key => $phases)
                @continue($phases->isEmpty())
                <div>
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('pickem.index.'.$key) }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($phases as $phase)
                            <a href="{{ route('pickem.phase.show', [$phase->tournament, $phase]) }}"
                               class="bg-bg-card border border-border-subtle rounded-lg p-4 hover:border-gc-yellow/50 transition-all">
                                <div class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ $phase->tournament->name }}</div>
                                <div class="text-sm font-bold text-white mt-1">{{ $phase->name }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
