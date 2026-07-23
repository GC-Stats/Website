{{--
    GC-Stats — Admin: match detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@php
    $vetoLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'matches.veto.edit');
    $editLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'matches.edit');
    $deleteLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'matches.delete');
    $resetMapsLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'maps.reset');
    $anyLocked = (auth()->user()->can('matches.veto.edit') && $vetoLocked)
        || (auth()->user()->can('matches.edit') && $editLocked)
        || (auth()->user()->can('matches.delete') && $deleteLocked)
        || (auth()->user()->can('maps.reset') && $resetMapsLocked);
@endphp

@section('title', \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status).' vs '.\App\Support\MatchDisplay::teamShortName($match->teamB, $match->status))

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <a href="{{ route('admin.matches.index', $tournament) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition">
            &larr; {{ __('admin.matches.title') }}
        </a>

        <a href="{{ route('match.show', $match->id) }}" target="_blank" rel="noopener"
           class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
            {{ __('admin.matches.public_page') }}
        </a>
    </div>

    @if ($anyLocked)
        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.matches.finished_locked') }}
        </div>
    @endif

    <div class="flex items-center justify-center gap-6 border-b border-white/10 pb-6 mb-6">
        <div class="flex flex-1 items-center justify-end gap-3 text-right">
            <div>
                <h2 class="text-xl font-black uppercase leading-none text-white">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</h2>
                <div class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ __('admin.matches.team_a') }}</div>
            </div>
            @if ($match->teamA)
                <img src="{{ $match->teamA->logo }}" alt="" class="h-14 w-14 object-contain">
            @endif
        </div>

        <div class="flex items-center gap-3 text-3xl font-black text-white">
            <span>{{ $match->team_a_score ?? 0 }}</span>
            <span class="text-gray-500">-</span>
            <span>{{ $match->team_b_score ?? 0 }}</span>
        </div>

        <div class="flex flex-1 items-center gap-3">
            @if ($match->teamB)
                <img src="{{ $match->teamB->logo }}" alt="" class="h-14 w-14 object-contain">
            @endif
            <div>
                <h2 class="text-xl font-black uppercase leading-none text-white">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</h2>
                <div class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ __('admin.matches.team_b') }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.matches.info.title') }}</h2>
                <div class="flex gap-2">
                    @can('matches.veto.edit')
                        <a href="{{ route('admin.matches.veto.edit', [$tournament, $match]) }}"
                           class="font-bold uppercase text-xs tracking-widest px-5 py-3 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10 {{ $vetoLocked ? 'pointer-events-none opacity-40' : '' }}">
                            {{ __('admin.matches.veto.title') }}
                        </a>
                    @endcan
                    @can('matches.edit')
                        <a href="{{ route('admin.matches.edit', [$tournament, $match]) }}"
                           class="font-bold uppercase text-xs tracking-widest px-5 py-3 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10 {{ $editLocked ? 'pointer-events-none opacity-40' : '' }}">
                            {{ __('admin.matches.edit.title') }}
                        </a>
                    @endcan
                    @can('matches.delete')
                        <form method="POST" action="{{ route('admin.matches.destroy', [$tournament, $match]) }}">
                            @csrf
                            @method('DELETE')
                            <x-confirm-modal
                                :title="__('admin.matches.delete.title')"
                                :body="__('admin.matches.delete.confirm_body')"
                                :trigger-label="__('admin.matches.delete.trigger')"
                                :submit-label="__('admin.matches.delete.trigger')"
                                :trigger-class="'font-bold uppercase text-xs tracking-widest px-5 py-3 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10'.($deleteLocked ? ' pointer-events-none opacity-40' : '')"
                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                            />
                        </form>
                    @endcan
                </div>
            </div>

            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-white/10">
                        <td class="py-2.5 pr-4 text-[10px] font-black uppercase tracking-widest text-gray-500 whitespace-nowrap">{{ __('admin.matches.status_column') }}</td>
                        <td class="py-2.5">
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg {{ $match->status === 'finished' ? 'bg-white/5 text-gray-400' : ($match->status === 'live' ? 'bg-red-500/10 text-red-400' : 'bg-green-500/10 text-green-400') }}">
                                {{ __('admin.matches.status.'.$match->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr class="border-b border-white/10">
                        <td class="py-2.5 pr-4 text-[10px] font-black uppercase tracking-widest text-gray-500 whitespace-nowrap">{{ __('admin.matches.scheduled_at') }}</td>
                        <td class="py-2.5 text-white font-bold">
                            @if (\App\Support\MatchDisplay::isUnknownDate($match->scheduled_at))
                                {{ __('admin.matches.unknown_date') }}
                            @else
                                <span data-utc-datetime="{{ $match->scheduled_at->copy()->utc()->toIso8601String() }}">
                                    <span class="js-match-date">{{ $match->scheduled_at->format('Y-m-d') }}</span>
                                    <span class="js-match-time">{{ $match->scheduled_at->format('H:i') }}</span>
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr class="border-b border-white/10">
                        <td class="py-2.5 pr-4 text-[10px] font-black uppercase tracking-widest text-gray-500 whitespace-nowrap">{{ __('admin.matches.best_of') }}</td>
                        <td class="py-2.5 text-white font-bold">BO{{ $match->best_of }}</td>
                    </tr>
                    <tr class="border-b border-white/10">
                        <td class="py-2.5 pr-4 text-[10px] font-black uppercase tracking-widest text-gray-500 whitespace-nowrap">{{ __('admin.matches.patch') }}</td>
                        <td class="py-2.5 text-white font-bold">{{ $match->patch ?? '—' }}</td>
                    </tr>
                    <tr class="border-b border-white/10">
                        <td class="py-2.5 pr-4 text-[10px] font-black uppercase tracking-widest text-gray-500 whitespace-nowrap">{{ __('admin.matches.phase') }}</td>
                        <td class="py-2.5 text-white font-bold">{{ $match->tournamentPhase->name ?? '—' }}</td>
                    </tr>
                    <tr class="border-b border-white/10">
                        <td class="py-2.5 pr-4 text-[10px] font-black uppercase tracking-widest text-gray-500 whitespace-nowrap">{{ __('admin.matches.round_name') }}</td>
                        <td class="py-2.5 text-white font-bold">{{ $match->round_name ?: '—' }} @if ($match->round_number) <span class="text-gray-500">#{{ $match->round_number }}</span> @endif</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pr-4 text-[10px] font-black uppercase tracking-widest text-gray-500 whitespace-nowrap">{{ __('admin.matches.match_order') }}</td>
                        <td class="py-2.5 text-gray-400 font-bold">#{{ $match->match_order ?? 1 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="lg:col-span-5 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.matches.maps.title') }}</h2>
                @can('maps.reset')
                    @if ($match->game_maps->isNotEmpty())
                        <form method="POST" action="{{ route('admin.matches.reset-maps', [$tournament, $match]) }}">
                            @csrf
                            @method('DELETE')
                            <x-confirm-modal
                                :title="__('admin.matches.maps.reset_all')"
                                :body="__('admin.matches.maps.reset_all_confirm')"
                                :trigger-label="__('admin.matches.maps.reset_all')"
                                :submit-label="__('admin.matches.maps.reset_all')"
                                :trigger-class="'font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10'.($resetMapsLocked ? ' pointer-events-none opacity-40' : '')"
                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                            />
                        </form>
                    @endif
                @endcan
            </div>

            <div class="space-y-2">
                @forelse ($match->game_maps as $map)
                    <a href="{{ route('admin.matches.maps.show', [$tournament, $match, $map]) }}"
                       class="relative flex min-h-[70px] items-center overflow-hidden rounded-lg bg-cover bg-center p-3 text-white shadow-sm hover:opacity-90 transition"
                       style="background-image: linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 100%), url('/storage/maps/{{ strtolower($map->map_name) }}.webp')">
                        <div class="z-10 flex w-full items-center justify-between">
                            <div>
                                <h6 class="font-black uppercase tracking-wide">{{ $map->map_name }}</h6>
                                <p class="text-xs text-gray-300">{{ \App\Support\MatchDisplay::mapScore($map->team_a_score, $map->team_b_score) }}</p>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg {{ $map->is_completed ? 'bg-green-500/10 text-green-400' : 'bg-white/5 text-gray-400' }}">
                                {{ $map->is_completed ? __('admin.matches.maps.finished') : __('admin.matches.maps.not_finished') }}
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="text-center text-xs text-gray-500 py-10">{{ __('admin.matches.maps.empty') }}</p>
                @endforelse
            </div>
        </div>

        @include('admin.matches._qualifications', ['tournament' => $tournament, 'match' => $match])
    </div>
@endsection
