{{--
    GC-Stats — Achievements panel (top-3 placements)

    Sidebar widget for team/player profile pages, only rendered when the
    entity has at least one top-3 result — see App\Support\Achievements.
    Design: 1:1 tiles (3 per row) with the tournament logo faint in the
    background, tournament name + gold/silver/bronze placement label over
    it; the tile border/logo brighten on hover. Capped to the 6 most recent
    :achievements passed in (already limited/sorted by the caller — see
    App\Support\Achievements::forEntity()).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['achievements' => []])

@php
    $tierColors = ['gold' => '#f2c14e', 'silver' => '#cfd8e0', 'bronze' => '#d08a4f'];
@endphp

@if (! empty($achievements))
    <div class="flex items-center gap-2 mb-3">
        <span class="text-[11px]" aria-hidden="true">🏆</span>
        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('achievements.title') }}</span>
        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
    </div>

    <div class="grid grid-cols-3 gap-1.5">
        @foreach ($achievements as $a)
            <a href="{{ $a['tournament_url'] }}"
               class="group relative aspect-square rounded-sm overflow-hidden border border-white/5 bg-[#050505] shadow-lg"
               style="--tier-color: {{ $tierColors[$a['tier']] }};">
                <img src="{{ $a['tournament_logo'] }}" alt=""
                     class="absolute inset-0 w-full h-full object-contain p-3 opacity-10 group-hover:opacity-20 transition-opacity duration-300">

                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-black/40"></div>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-1 gap-0.5">
                    <span class="text-[8px] font-black uppercase tracking-tight text-white leading-tight line-clamp-2">{{ $a['tournament_name'] }}</span>
                    <span class="text-[7px] font-bold uppercase tracking-widest" style="color: {{ $tierColors[$a['tier']] }}">{{ $a['label'] }}</span>
                </div>

                <div class="absolute inset-0 rounded-sm border-2 border-transparent group-hover:border-[var(--tier-color)] transition-colors duration-300 pointer-events-none"></div>
            </a>
        @endforeach
    </div>
@endif
