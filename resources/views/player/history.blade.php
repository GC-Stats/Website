{{--
    GC-Stats — Player transaction history page

    Displays a player's career history (team transfers, joins/departures
    with dates).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('player.title.history', ["player" => $player['handle']]))

@section('content')
    @include('player.header')

    <div class="max-w-6xl mx-auto space-y-4">
        <div class="border-b border-border-subtle pb-2 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                {{ __("player.teams_history", ["player" => $player['handle']]) }}
            </h2>
        </div>

        @forelse($pastTeams as $team)
            <a href="{{ route('teams.show', [$team['id'], str($team['name'] ?? '')->slug()]) }}" class="group block mb-2">
                <div class="tournament-card bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="relative shrink-0">
                                <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                <img class="w-12 h-12 object-contain mr-2" src="{{ $team['logo'] ?? asset('storage/images/default-team.webp') }}" alt="{{ $team['name'] }}">
                            </div>

                            <div class="min-w-0">
                                <p class="text-base font-bold text-white truncate">{{ $team['name'] }}</p>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">
                                    {{ \App\Helpers\RosterRole::label($team['pivot']['role'] ?? null) ?? 'Player' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-[11px] font-mono font-bold text-gray-300 uppercase">
                                {{ isset($team['pivot']['joined_at']) ? (\App\Helpers\PivotDate::format($team['pivot']['joined_at'], 'M Y') ?? '???') : '???' }}
                                <span class="mx-2 text-gray-600">—</span>
                                {{ isset($team['pivot']['left_at']) ? (\App\Helpers\PivotDate::format($team['pivot']['left_at'], 'M Y') ?? 'Present') : 'Present' }}
                            </p>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <h3 class="text-center text-gray-400">{{ __('player.empty.players_history') }}</h3>
        @endforelse

        <div class="mt-8">
            {{ $pastTeams->links() }}
        </div>
    </div>
@endsection
