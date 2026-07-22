{{--
    GC-Stats — Tournament matches page

    Lists all matches of the tournament, grouped by phase, with a phase
    selector.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('tournament.title.matches', ["tournament" => $tournament['name']]))

@section('content')
    <div x-data="{ activePhase: {{ $root_phases[0]['id'] ?? 0 }} }">
       @include("tournament.header")

        @if($inactive_access ?? false)
            <div class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 text-xs text-gc-yellow">
                {{ __('tournament.inactive_access') }}
            </div>
        @endif

        <div class="max-w-6xl mx-auto">
            <div class="pb-2 flex justify-between items-end mb-3 border-b border-border-subtle">
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                    {{ __("tournament.last_matches") }}
                </h2>
            </div>

            @php
                $selectedPhase = collect($filters['phases'])->firstWhere('id', (int) $phaseId);
                $selectedTeam = collect($filters['teams'])->firstWhere('id', (int) $teamId);
                $availableRounds = $selectedPhase ? ($filters['rounds'][$selectedPhase['id']] ?? []) : [];
            @endphp

            <div class="relative z-20 mb-4">
                <div class="bg-black/40 backdrop-blur-md border border-white/10 rounded-xl p-3 shadow-2xl space-y-3">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                        @if(!empty($filters['phases']))
                            <div class="flex items-center gap-4 lg:shrink-0"
                                 x-data="{
                                    open: false,
                                    search: '',
                                    phases: {{ json_encode($filters['phases']) }},
                                    goTo(phaseId) {
                                        const url = new URL(window.location.href);
                                        if (phaseId) { url.searchParams.set('phase_id', phaseId); }
                                        else { url.searchParams.delete('phase_id'); }
                                        url.searchParams.delete('round');
                                        url.searchParams.delete('page');
                                        window.location.href = url.toString();
                                    }
                                 }"
                                 @click.outside="open = false">
                                <span class="text-[9px] uppercase font-black text-gray-500 tracking-[0.2em] shrink-0">
                                    {{ __("tournament.filters.phase") }}
                                </span>

                                <div class="relative w-full lg:w-56">
                                    <button type="button" @click="open = !open"
                                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-[#0c0c0c] border border-white/5 transition-all duration-300
                                            {{ $selectedPhase ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                        <span class="truncate">{{ $selectedPhase['name'] ?? __("tournament.filters.all_phases") }}</span>
                                        <x-fas-chevron-down class="w-2.5 h-2.5 shrink-0" aria-hidden="true" />
                                    </button>

                                    <div x-show="open" x-cloak x-transition
                                         class="absolute z-50 mt-1 w-full bg-[#0c0c0c] border border-white/10 rounded-lg shadow-2xl overflow-hidden">
                                        <div class="p-2 border-b border-white/5">
                                            <input type="text" x-model="search" x-ref="search"
                                                   @keydown.escape="open = false"
                                                   placeholder="{{ __('tournament.filters.search_phase') }}"
                                                   class="w-full bg-white/[0.03] border border-white/5 rounded-md px-2 py-1.5 text-[10px] font-bold uppercase text-gray-300 focus:outline-none focus:border-[var(--brand-yellow)]/30">
                                        </div>

                                        <div class="max-h-48 overflow-y-auto">
                                            <button type="button" @click="goTo(null); open = false"
                                                    class="w-full text-left px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                                                {{ __("tournament.filters.all_phases") }}
                                            </button>

                                            <template x-for="phase in phases.filter(p => p.name.toLowerCase().includes(search.toLowerCase()))" :key="phase.id">
                                                <button type="button" @click="goTo(phase.id); open = false"
                                                        class="w-full text-left px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-white hover:bg-white/5 transition-colors"
                                                        :class="phase.id === {{ $phaseId ? (int) $phaseId : 0 }} ? 'text-[var(--brand-yellow)]' : ''"
                                                        x-text="phase.name">
                                                </button>
                                            </template>

                                            <template x-if="phases.filter(p => p.name.toLowerCase().includes(search.toLowerCase())).length === 0">
                                                <p class="px-3 py-2 text-[10px] font-bold uppercase text-gray-600">—</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(!empty($filters['teams']))
                            <div class="flex items-center gap-4 lg:shrink-0"
                                 x-data="{
                                    open: false,
                                    search: '',
                                    teams: {{ json_encode($filters['teams']) }},
                                    goTo(teamId) {
                                        const url = new URL(window.location.href);
                                        if (teamId) { url.searchParams.set('team_id', teamId); }
                                        else { url.searchParams.delete('team_id'); }
                                        url.searchParams.delete('page');
                                        window.location.href = url.toString();
                                    }
                                 }"
                                 @click.outside="open = false">
                                <span class="text-[9px] uppercase font-black text-gray-500 tracking-[0.2em] shrink-0">
                                    {{ __("tournament.filters.team") }}
                                </span>

                                <div class="relative w-full lg:w-56">
                                    <button type="button" @click="open = !open"
                                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-[#0c0c0c] border border-white/5 transition-all duration-300
                                            {{ $selectedTeam ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                        <span class="truncate">{{ $selectedTeam['name'] ?? __("tournament.filters.all_teams") }}</span>
                                        <x-fas-chevron-down class="w-2.5 h-2.5 shrink-0" aria-hidden="true" />
                                    </button>

                                    <div x-show="open" x-cloak x-transition
                                         class="absolute z-50 mt-1 w-full bg-[#0c0c0c] border border-white/10 rounded-lg shadow-2xl overflow-hidden">
                                        <div class="p-2 border-b border-white/5">
                                            <input type="text" x-model="search" x-ref="search"
                                                   @keydown.escape="open = false"
                                                   placeholder="{{ __('tournament.filters.search_team') }}"
                                                   class="w-full bg-white/[0.03] border border-white/5 rounded-md px-2 py-1.5 text-[10px] font-bold uppercase text-gray-300 focus:outline-none focus:border-[var(--brand-yellow)]/30">
                                        </div>

                                        <div class="max-h-48 overflow-y-auto">
                                            <button type="button" @click="goTo(null); open = false"
                                                    class="w-full text-left px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                                                {{ __("tournament.filters.all_teams") }}
                                            </button>

                                            <template x-for="team in teams.filter(t => t.name.toLowerCase().includes(search.toLowerCase()))" :key="team.id">
                                                <button type="button" @click="goTo(team.id); open = false"
                                                        class="w-full text-left px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-white hover:bg-white/5 transition-colors"
                                                        :class="team.id === {{ $teamId ? (int) $teamId : 0 }} ? 'text-[var(--brand-yellow)]' : ''"
                                                        x-text="team.name">
                                                </button>
                                            </template>

                                            <template x-if="teams.filter(t => t.name.toLowerCase().includes(search.toLowerCase())).length === 0">
                                                <p class="px-3 py-2 text-[10px] font-bold uppercase text-gray-600">—</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 flex-wrap">
                        <span class="text-[9px] uppercase font-black text-gray-500 tracking-[0.2em] shrink-0">
                            {{ __("tournament.filters.status") }}
                        </span>

                        <div class="flex flex-wrap bg-white/[0.03] p-1 rounded-lg border border-white/5 gap-1">
                            <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
                               class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md transition-all duration-300
                           {{ !$status ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                {{ __("tournament.filters.all_statuses") }}
                            </a>

                            @foreach(['live' => 'match.status.live', 'upcoming' => 'match.status.upcoming', 'finished' => 'match.status.finished'] as $statusOption => $statusLabel)
                                <a href="{{ request()->fullUrlWithQuery(['status' => $statusOption, 'page' => null]) }}"
                                   class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md transition-all duration-300
                               {{ $status === $statusOption ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                    {{ __($statusLabel) }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if($selectedPhase && in_array($selectedPhase['format'], \App\Models\TournamentPhase::RANK_BASED_FORMATS) && !empty($availableRounds))
                        <div class="flex items-center gap-4 flex-wrap">
                            <span class="text-[9px] uppercase font-black text-gray-500 tracking-[0.2em] shrink-0">
                                {{ __("tournament.filters.round") }}
                            </span>

                            <div class="flex flex-wrap bg-white/[0.03] p-1 rounded-lg border border-white/5 gap-1">
                                <a href="{{ request()->fullUrlWithQuery(['round' => null, 'page' => null]) }}"
                                   class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md transition-all duration-300
                               {{ !$roundName ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                    {{ __("tournament.filters.all_rounds") }}
                                </a>

                                @foreach($availableRounds as $roundOption)
                                    <a href="{{ request()->fullUrlWithQuery(['round' => $roundOption, 'page' => null]) }}"
                                       class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md transition-all duration-300
                               {{ $roundName == $roundOption ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                        {{ $roundOption }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                @forelse($matches as $match)
                    <x-match :match="$match" />
                @empty
                    <p class="text-center text-gray-500 text-sm py-12 uppercase font-bold tracking-widest">
                        {{ __("tournament.no_match") }}
                    </p>
                @endforelse
            </div>

            <div class="mt-8 mb-12">
                {{ $matches->links() }}
            </div>
        </div>
    </div>
@endsection
