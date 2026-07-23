{{--
    GC-Stats — Admin: dashboard

    Landing page for /admin once a user has general content-viewing access:
    a quick headcount of the site's core content. Users without any of the
    tournaments/teams/players permissions never reach this view — see
    Admin\DashboardController::index.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.dashboard.title'))

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $cards = [
                ['key' => 'tournaments', 'icon' => 'fas-trophy', 'route' => 'admin.tournaments.index'],
                ['key' => 'teams', 'icon' => 'fas-people-group', 'route' => 'admin.teams.index'],
                ['key' => 'players', 'icon' => 'fas-user', 'route' => 'admin.players.index'],
                ['key' => 'matches', 'icon' => 'fas-gamepad', 'route' => 'admin.tournaments.index'],
            ];
        @endphp

        @foreach ($cards as $card)
            @continue(is_null($stats[$card['key']]))
            <a href="{{ route($card['route']) }}"
               class="group bg-bg-card border border-white/5 rounded-xl p-5 flex items-center gap-4 transition-all duration-300 hover:border-[var(--brand-yellow)]/40 hover:shadow-[0_0_30px_rgba(0,0,0,0.5)]">
                <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-[var(--brand-yellow)]/10 text-[var(--brand-yellow)] shrink-0 group-hover:bg-[var(--brand-yellow)]/15 transition-colors">
                    @svg($card['icon'], 'w-4.5 h-4.5', ['aria-hidden' => 'true'])
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-black tracking-tight text-white leading-none">{{ number_format($stats[$card['key']]) }}</p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mt-1.5">{{ __('admin.dashboard.'.$card['key']) }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4 items-start">
        @if ($recentTournaments)
            @php
                $regionColors = config('regions.colors', []);
                // "Game Changers 2026" -> "GC 2026"
                $shortTournamentName = fn (string $name) => preg_replace('/^Game Changers\b/i', 'GC', $name);
            @endphp
            <div class="bg-bg-card border border-white/5 rounded-xl overflow-hidden" x-data="{ tab: '{{ request('tournaments_tab', 'live') }}' }">
                <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
                    @foreach (['live', 'upcoming', 'inactive'] as $tab)
                        <button type="button" @click="tab = '{{ $tab }}'"
                                class="text-[10px] font-black uppercase tracking-widest transition"
                                :class="tab === '{{ $tab }}' ? 'text-[var(--brand-yellow)]' : 'text-gray-500 hover:text-gray-300'">
                            {{ __('admin.dashboard.tournaments_widget.tabs.'.$tab) }}
                        </button>
                    @endforeach
                </div>

                @foreach (['live', 'upcoming', 'inactive'] as $tab)
                    <div x-show="tab === '{{ $tab }}'" x-cloak>
                        @forelse ($recentTournaments[$tab] as $tournament)
                            <a href="{{ route('admin.tournaments.show', $tournament) }}"
                               class="block px-4 py-3 border-b border-white/5 last:border-0 hover:bg-white/5 transition">
                                <div class="flex items-center justify-between gap-2 min-h-5">
                                    <span class="text-xs font-bold text-white truncate">{{ $shortTournamentName($tournament->name) }}</span>
                                    <span class="flex items-center gap-1.5 shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $tournament->active ? 'bg-green-500' : 'bg-red-500' }}"
                                              title="{{ $tournament->active ? __('admin.tournaments.active') : __('admin.tournaments.inactive') }}"></span>
                                        <span class="text-[10px] text-gray-500">{{ optional($tournament->start_date)->format('Y-m-d') ?? '—' }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    @php $color = $regionColors[$tournament->region] ?? '#888888'; @endphp
                                    <span class="inline-block px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md"
                                          style="color: {{ $color }}; background: {{ $color }}15;">
                                        {{ $tournament->region }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-center text-gray-500 text-xs">{{ __('admin.dashboard.tournaments_widget.empty') }}</p>
                        @endforelse

                        @include('admin.partials.mini-pagination', ['paginator' => $recentTournaments[$tab]])
                    </div>
                @endforeach
            </div>
        @endif

        @if ($recentTeamModifications)
            @include('admin.partials.dashboard-modifications-widget', ['type' => 'team', 'activities' => $recentTeamModifications])
        @endif

        @if ($recentPlayerModifications)
            @include('admin.partials.dashboard-modifications-widget', ['type' => 'player', 'activities' => $recentPlayerModifications])
        @endif

        @if ($recentMatches)
            <div class="bg-bg-card border border-white/5 rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-white/5">
                    <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('admin.dashboard.matches_widget.title') }}</h2>
                </div>

                @forelse ($recentMatches as $match)
                    <a href="{{ route('admin.matches.show', [$match->tournament, $match]) }}"
                       class="block px-4 py-3 border-b border-white/5 last:border-0 hover:bg-white/5 transition">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <img src="{{ $match->teamA?->logo ?? asset('storage/images/default-team.webp') }}"
                                     alt="" class="w-5 h-5 object-contain shrink-0">
                                <span class="text-xs font-bold text-white truncate">{{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }}</span>
                            </div>
                            <span class="text-xs font-black text-gray-400 shrink-0 px-1">
                                {{ ! is_null($match->team_a_score) ? $match->team_a_score.' - '.$match->team_b_score : 'vs' }}
                            </span>
                            <div class="flex items-center gap-1.5 min-w-0 justify-end">
                                <span class="text-xs font-bold text-white truncate text-right">{{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}</span>
                                <img src="{{ $match->teamB?->logo ?? asset('storage/images/default-team.webp') }}"
                                     alt="" class="w-5 h-5 object-contain shrink-0">
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded {{ $match->status === 'live' ? 'bg-red-500/10 text-red-400' : 'bg-green-500/10 text-green-400' }}">
                                {{ __('admin.matches.status.'.$match->status) }}
                            </span>
                            <span class="text-[10px] text-gray-500">
                                @if (\App\Support\MatchDisplay::isUnknownDate($match->scheduled_at))
                                    {{ __('admin.matches.unknown_date') }}
                                @else
                                    <span data-utc-datetime="{{ $match->scheduled_at->copy()->utc()->toIso8601String() }}">
                                        <span class="js-match-date">{{ $match->scheduled_at->format('Y-m-d') }}</span>
                                        <span class="js-match-time">{{ $match->scheduled_at->format('H:i') }}</span>
                                    </span>
                                @endif
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-gray-500 text-xs">{{ __('admin.dashboard.matches_widget.empty') }}</p>
                @endforelse

                @include('admin.partials.mini-pagination', ['paginator' => $recentMatches])
            </div>
        @endif
    </div>
@endsection
