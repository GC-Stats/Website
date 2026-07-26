{{--
    GC-Stats — Developers: dashboard

    Landing page for /developers once a user has general content-viewing access:
    a quick headcount of the site's core content. Users without any of the
    tournaments/teams/players permissions never reach this view — see
    developers\DashboardController::index.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('developers.layout')

@section('title', __('developers.dashboard.title'))

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $defaultApiKey = auth()->user()->apiKeys()->oldest()->first();

            $cards = [
                ['key' => 'api-keys', 'icon' => 'fas-key', 'route' => 'developers.api-keys.index', 'params' => []],
                ['key' => 'requests', 'icon' => 'fas-server', 'route' => $defaultApiKey ? 'developers.requests.index' : null, 'params' => $defaultApiKey ? ['key' => $defaultApiKey] : []],
                ['key' => 'avg_response_time', 'icon' => 'fas-clock', 'route' => null, 'params' => []],
                ['key' => 'error_rate', 'icon' => 'fas-circle-exclamation', 'route' => null, 'params' => []],
            ];
        @endphp

        @foreach ($cards as $card)
            <a href="{{ $card['route'] ? route($card['route'], $card['params']) : '#' }}"
                class="group bg-bg-card border border-white/5 rounded-xl p-5 flex items-center gap-4 transition-all duration-300 hover:border-[var(--brand-yellow)]/40 hover:shadow-[0_0_30px_rgba(0,0,0,0.5)]">
                <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-[var(--brand-yellow)]/10 text-[var(--brand-yellow)] shrink-0 group-hover:bg-[var(--brand-yellow)]/15 transition-colors">
                    @svg($card['icon'], 'w-4.5 h-4.5', ['aria-hidden' => 'true'])
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-black tracking-tight text-white leading-none">{{ $stats[$card['key']] }}</p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mt-1.5">{{ __('developers.dashboard.overview.'.$card['key']) }}</p>
                </div>
            </a>
        @endforeach
    </div>


@endsection
