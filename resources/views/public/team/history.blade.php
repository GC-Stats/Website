{{--
    GC-Stats — Team transaction history page

    Displays a team's roster history (player transfers, joins/departures
    with dates).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('team.title.history', ["team" => $team['name']]))

@section('content')
    @include('public.team.header')

    <div class="max-w-6xl mx-auto">
        <div class="border-b border-border-subtle pb-2 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                {{ __("team.players_history", ["team" => $team['name']]) }}
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($pastPlayers as $player)
                @php $historyRosterRole = $player['pivot']['role'] ?? null; @endphp
                <a href="{{ route('players.show', [$player['id'], str($player['handle'] ?? '')->slug()]) }}" class="group block mb-2">
                    <div class="tournament-card flex bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                        <div class="w-1 shrink-0 {{ \App\Helpers\RosterRole::barClass($historyRosterRole) }}"></div>

                        <div class="flex items-center justify-between gap-4 p-3 min-w-0 flex-1">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="relative shrink-0">
                                    @if($player['profile_photo'])
                                        <img src="{{ $player['profile_photo'] }}" alt="{{ $player['handle'] }}"
                                             class="w-10 h-10 object-contain border border-white/10 rounded-lg bg-black/40">
                                    @else
                                        <div class="w-10 h-10 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10">
                                            <span class="text-md md:text-l font-black text-[var(--brand-yellow)]">
                                                {{ strtoupper(substr($player['handle'], 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <p class="flex items-center gap-1.5 text-base font-bold text-white truncate">
                                        <span class="fi fi-{{ blank($player['country_code']) || $player['country_code'] === 'inter' ? 'un' : strtolower($player['country_code']) }} shadow-sm shrink-0"
                                              aria-label="{{ $player['country_code'] ?? '' }}" role="img"></span>
                                        {{ $player['handle'] }}
                                    </p>
                                    <span class="inline-block mt-1 text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm {{ \App\Helpers\RosterRole::badgeClass($historyRosterRole) }}">
                                        {{ \App\Helpers\RosterRole::label($historyRosterRole) ?? __('team.roster.roles.player') }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <p class="text-[11px] font-mono font-bold text-gray-300 uppercase">
                                    {{ isset($player['pivot']['joined_at']) ? (\App\Helpers\PivotDate::format($player['pivot']['joined_at'], 'M Y') ?? '???') : '???' }}
                                    <span class="mx-2 text-gray-600">—</span>
                                    {{ isset($player['pivot']['left_at']) ? (\App\Helpers\PivotDate::format($player['pivot']['left_at'], 'M Y') ?? __('team.roster.now')) : __('team.roster.now') }}
                                </p>
                                @if(!empty($player['pivot']['inactive_since']))
                                    <p class="mt-0.5 text-[9px] font-bold text-gray-500 uppercase tracking-widest">
                                        {{ __('team.roster.inactive_since', ['date' => \App\Helpers\PivotDate::format($player['pivot']['inactive_since'], 'M Y') ?? __('team.roster.unknown_date')]) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <h3 class="text-center text-gray-400">{{ __('team.empty.players_history') }}</h3>
            @endforelse
        </div>

        <div class="mt-8 mb-12">
            {{ $pastPlayers->links() }}
        </div>
    </div>
@endsection
