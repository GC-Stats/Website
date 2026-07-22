{{--
    GC-Stats — Tournament overview page

    Displays the tournament overview: header, bracket/standings (via the
    Livewire bracket component) and participating teams.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('tournament.title.index', ["tournament" => $tournament['name']]))
@section('description', \Illuminate\Support\Str::limit(strip_tags($tournament['description'] ?? ''), 160) ?: __('tournament.title.index', ["tournament" => $tournament['name']]))
@section('canonical', route('tournaments.show', [$tournament['id'], str($tournament['name'] ?? '')->slug()]))
@section('og_image', $tournament['logo'] ?? asset('web-app-manifest-512x512.png'))

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'SportsEvent',
    'name' => $tournament['name'],
    'startDate' => $tournament['start_date'] ?? null,
    'endDate' => $tournament['end_date'] ?? null,
    'image' => $tournament['logo'] ?? null,
    'url' => route('tournaments.show', [$tournament['id'], str($tournament['name'] ?? '')->slug()]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
    <div x-data="{
        activePhase: (() => {
            const params = new URLSearchParams(window.location.search);
            const fromUrl = parseInt(params.get('phase'));
            const valid = [{{ implode(',', array_column($root_phases, 'id')) }}];
            return fromUrl && valid.includes(fromUrl) ? fromUrl : {{ $root_phases[0]['id'] ?? 0 }};
        })(),
        setPhase(id) {
            this.activePhase = id;
            const url = new URL(window.location);
            url.searchParams.set('phase', id);
            history.pushState({}, '', url);
        }
    }">
        @include("tournament.header")

        @if($inactive_access ?? false)
            <div class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 text-xs text-gc-yellow">
                {{ __('tournament.inactive_access') }}
            </div>
        @endif

        @if(!empty($root_phases))
            <div class="mb-8 sticky top-20 z-40">
                <div class="flex items-center flex-wrap gap-2 p-1.5 bg-white/[0.02] border border-white/5 rounded-xl backdrop-blur-xl">
                    @foreach($root_phases as $phase)
                        <button @click="setPhase({{ $phase['id'] }})"
                                class="relative px-5 py-2.5 transition-all duration-300 rounded-lg group outline-none overflow-hidden"
                                :class="activePhase === {{ $phase['id'] }} ? 'bg-[var(--brand-yellow)]' : 'bg-white/5 hover:bg-white/10'">

                            <div class="flex items-center gap-3 relative z-10">
                                <div :class="activePhase === {{ $phase['id'] }} ? 'bg-black' : 'bg-gray-600 group-hover:bg-[var(--brand-yellow)]'"
                                     class="w-1.5 h-1.5 rounded-full transition-all duration-500"></div>

                                <div class="flex flex-col items-start">
                                    <span :class="activePhase === {{ $phase['id'] }} ? 'text-black font-black' : 'text-gray-400 font-bold group-hover:text-white'"
                                          class="text-[10px] uppercase tracking-[0.15em] transition-colors">
                                        {{ $phase['name'] }}
                                    </span>

                                    @if ($phase['start_date'] ?? $phase['end_date'] ?? null)
                                        <span :class="activePhase === {{ $phase['id'] }} ? 'text-black/60' : 'text-gray-500'"
                                              class="text-[9px] font-semibold tracking-wide transition-colors">
                                            @if (($phase['start_date'] ?? null) && ($phase['end_date'] ?? null))
                                                {{ \Carbon\Carbon::parse($phase['start_date'])->format('d M') }} &ndash; {{ \Carbon\Carbon::parse($phase['end_date'])->format('d M Y') }}
                                            @elseif ($phase['start_date'] ?? null)
                                                {{ \Carbon\Carbon::parse($phase['start_date'])->format('d M Y') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($phase['end_date'])->format('d M Y') }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div x-show="activePhase === {{ $phase['id'] }}"
                                 class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full animate-[shimmer_2s_infinite]">
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <style>
                @keyframes shimmer {
                    100% { transform: translateX(100%); }
                }
            </style>
        @endif

        <div class="grid grid-cols-12 gap-6">
            <main class="col-span-12 lg:col-span-8 xl:col-span-9">
                @foreach($root_phases as $phase)
                    <div x-show="activePhase === {{ $phase['id'] }}" x-cloak>
                        @livewire('tournament.phase', ['phase' => $phase, 'teams' => $teams], key('bracket-'.$phase['id']))
                    </div>
                @endforeach
            </main>

            <aside class="col-span-12 lg:col-span-4 xl:col-span-3">
                <div class="sticky top-32">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __("tournament.last_matches") }}</span>
                        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                        <a href="{{ route('tournaments.matches', [$tournament['id'], Str::routeSlug($tournament['name'] ?? '', $tournament['id'])]) }}" class="group flex items-center gap-1 text-[9px] font-black uppercase tracking-[0.15em] text-white/30 hover:text-gc-yellow transition-colors shrink-0">
                            <span>{{ __("tournament.seemore") }}</span>
                            <x-fas-chevron-right class="w-2.5 h-2.5 inline-block transform group-hover:translate-x-0.5 transition-transform" aria-hidden="true" />
                        </a>
                    </div>

                    <div class="space-y-3">
                        @forelse($matches as $m)
                            <a href="{{ route('match.show', $m['id']) }}" class="group block mb-2">
                                <div class="tournament-card bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                                    <div class="flex justify-center mb-2">
                                        @if($m['status'] == 'live')
                                            <div class="flex items-center gap-1.5 px-2 py-0.5 bg-red-500/10 border border-red-500/20 rounded-full" role="status" aria-live="polite">
                                                <span class="relative flex h-1.5 w-1.5" aria-hidden="true">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
                                                </span>
                                                <span class="text-[8px] font-black text-red-500 uppercase tracking-widest">{{ __('index.live') }}</span>
                                            </div>
                                        @elseif($m['status'] == 'upcoming')
                                            <div class="flex items-center gap-1.5 px-2 py-0.5 bg-green-500/10 border border-green-500/20 rounded-full">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>
                                                <span class="text-[8px] font-black text-green-500 uppercase tracking-widest">{{ __('match.status.upcoming') }}</span>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-1.5 px-2 py-0.5 bg-white/5 border border-white/10 rounded-full">
                                                <span class="h-1.5 w-1.5 rounded-full bg-gray-500" aria-hidden="true"></span>
                                                <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">{{ __('match.status.finished') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="relative shrink-0 flex flex-1 flex-col items-center min-w-0">
                                            <div class="relative shrink-0">
                                                <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                                <img src="{{ $m['team_a']['logo'] ?? asset('storage/images/default-team.webp') }}" class="relative w-8 h-8 object-contain mb-1" alt="">
                                            </div>
                                            <span class="text-[9px] font-black text-white uppercase truncate w-full text-center">
                                                {{ $m['team_a']['short_name'] ?? ($m['team_a']['name'] ?? ($m['status'] == 'finished' ? 'BYE' : 'TBD')) }}
                                            </span>
                                        </div>

                                        <div class="flex flex-col items-center px-4 shrink-0">
                                            <span class="text-base font-black text-white italic">
                                                {{ $m['team_a_score'] == -1 ? 'FF' : ($m['team_a_score'] ?? 0) }} - {{ $m['team_b_score'] == -1 ? 'FF' : ($m['team_b_score'] ?? 0) }}
                                            </span>
                                            @if(\App\Helpers\PivotDate::isUnknown($m['scheduled_at'] ?? null))
                                                <span class="text-[8px] font-bold text-gray-500 uppercase mt-1">
                                                    {{ __('match.unknown_date') }}
                                                </span>
                                            @else
                                                <span class="text-[8px] font-bold text-gray-500 uppercase mt-1" data-utc-datetime="{{ \Carbon\Carbon::parse($m['scheduled_at'], 'UTC')->toIso8601String() }}">
                                                    <span class="js-match-date">{{ \Carbon\Carbon::parse($m['scheduled_at'])->format('d/m H:i') }}</span>
                                                    <span class="js-match-time">{{ \Carbon\Carbon::parse($m['scheduled_at'])->translatedFormat('H:i') }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        <div class="relative shrink-0 flex flex-1 flex-col items-center min-w-0">
                                            <div class="relative shrink-0">
                                                <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                                <img src="{{ $m['team_b']['logo'] ?? asset('storage/images/default-team.webp') }}" class="relative w-8 h-8 object-contain mb-1" alt="">
                                            </div>
                                            <span class="text-[9px] font-black text-white uppercase truncate w-full text-center">
                                                {{ $m['team_b']['short_name'] ?? ($m['team_b']['name'] ?? ($m['status'] == 'finished' ? 'BYE' : 'TBD')) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="py-10 text-center border border-dashed border-border-subtle rounded-sm">
                                <span class="text-[9px] font-black text-gray-600 uppercase italic">{{ __("tournament.no_match") }}</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        @include('news._sidebar', ['news' => $news, 'sectionTitle' => __('news.press_section')])
                    </div>
                </div>
            </aside>
        </div>

        <div class="mt-12" x-data="{ showAllRosters: false }">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __("tournament.teams_participating") }}</span>
                <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                <button @click="showAllRosters = !showAllRosters" type="button" class="flex items-center gap-1 text-[9px] font-black uppercase tracking-[0.15em] text-white/60 hover:text-gc-yellow transition-colors shrink-0">
                    <span x-text="showAllRosters ? '{{ __('tournament.hide_all_rosters') }}' : '{{ __('tournament.show_all_rosters') }}'"></span>
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4 mt-4">
                @foreach($teams as $team)
                    <div x-data="{ showRoster: false }" x-init="$watch('showAllRosters', () => showRoster = false)" class="relative flex flex-col items-center text-center tournament-card bg-[#050505] border border-white/5 rounded-sm p-3 transition-all duration-300 shadow-lg h-full" ::class="(showAllRosters ? !showRoster : showRoster) ? '!border-white/5' : 'hover:bg-bg-main hover:border-[var(--brand-yellow)]/30'">
                        <a href="{{ route('teams.show', [$team['id'], str($team['name'] ?? '')->slug()]) }}" class="group flex flex-col items-center w-full">
                            <span class="text-[10px] font-black text-white uppercase italic tracking-tight truncate w-full">
                                {{ $team['name'] }}
                            </span>
                        </a>

                        <div class="flex-1 flex items-center justify-center w-full">
                            <a href="{{ route('teams.show', [$team['id'], str($team['name'] ?? '')->slug()]) }}" x-show="!(showAllRosters ? !showRoster : showRoster)" class="group relative shrink-0 w-16 h-16 flex items-center justify-center p-2 group-hover:scale-110 transition-transform">
                                <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                <img class="max-w-full max-h-full object-contain logo-filter" src="{{ $team['logo'] ?? asset('storage/images/default-team.webp') }}" alt="" loading="lazy">
                            </a>

                            @if(!empty($team['roster']))
                                <div x-show="showAllRosters ? !showRoster : showRoster" x-cloak class="w-full flex flex-col items-center justify-center gap-1 bg-[#0A0A0A] rounded-sm py-3 px-1">
                                    @foreach($team['roster'] as $player)
                                        <div class="text-[10px] font-bold text-gray-200 uppercase truncate w-full leading-tight">{{ $player['handle'] }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if(!empty($team['roster']))
                            <button @click="showRoster = !showRoster" type="button" class="mt-2 flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-white/10 bg-white/5 hover:bg-white/10 hover:border-[var(--brand-yellow)]/40 text-[10px] font-bold uppercase tracking-[0.15em] text-gray-300 hover:text-[var(--brand-yellow)] transition-colors">
                                <span x-text="(showAllRosters ? !showRoster : showRoster) ? '{{ __('tournament.hide_roster') }}' : '{{ __('tournament.show_roster') }}'"></span>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
