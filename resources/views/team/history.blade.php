{{--
    GC-Stats — Team transaction history page

    Displays a team's roster history (player transfers, joins/departures
    with dates).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('team.title.history', ["team" => $team['name']]))

@section('content')
    @include('team.header')

    <div class="max-w-6xl mx-auto">
        <div class="border-b border-border-subtle pb-2 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                {{ __("team.players_history", ["team" => $team['name']]) }}
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($pastPlayers as $player)
                <a href="{{ route('players.show', [$player['id'], str($player['handle'] ?? '')->slug()]) }}" class="group block mb-2">
                    <div class="tournament-card bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="relative shrink-0">
                                    @if($player['profile_photo'])
                                        <img src="{{ $player['profile_photo'] }}" alt="{{ $player['handle'] }}"
                                             class="w-10 h-10 object-cover border border-white/10 rounded-lg bg-black/40">
                                    @else
                                        <div class="w-10 h-10 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10">
                                            <span class="text-md md:text-l font-black text-[var(--brand-yellow)]">
                                                {{ strtoupper(substr($player['handle'], 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <p class="text-base font-bold text-white truncate">{{ $player['handle'] }}</p>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">
                                        {{ $player['pivot']['role'] ?? 'Player' }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <p class="text-[11px] font-mono font-bold text-gray-300 uppercase">
                                    {{ isset($player['pivot']['joined_at']) ? (\App\Helpers\PivotDate::format($player['pivot']['joined_at'], 'M Y') ?? '???') : '???' }}
                                    <span class="mx-2 text-gray-600">—</span>
                                    {{ isset($player['pivot']['left_at']) ? (\App\Helpers\PivotDate::format($player['pivot']['left_at'], 'M Y') ?? 'Present') : 'Present' }}
                                </p>
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
