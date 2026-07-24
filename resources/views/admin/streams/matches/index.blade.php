{{--
    GC-Stats — Admin: matches with a linked stream (list all)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.streams.matches.title'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ __('admin.streams.matches.title') }}</h1>

        <a href="{{ route('admin.streams.matches.create') }}"
           class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            + {{ __('admin.streams.matches.create_title') }}
        </a>
    </div>

    <form method="GET" class="mb-6 flex flex-wrap gap-3">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <select name="status" onchange="this.form.submit()"
                class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            <option value="active" @selected($status === 'active')>{{ __('admin.streams.matches.status_active') }}</option>
            <option value="all" @selected($status === 'all')>{{ __('admin.streams.matches.status_all') }}</option>
            <option value="upcoming" @selected($status === 'upcoming')>{{ __('admin.matches.status.upcoming') }}</option>
            <option value="live" @selected($status === 'live')>{{ __('admin.matches.status.live') }}</option>
            <option value="finished" @selected($status === 'finished')>{{ __('admin.matches.status.finished') }}</option>
        </select>
    </form>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.streams.matches.match') }}</th>
                    <x-admin.sortable-th col="tournament" :sort="$sort" :direction="$direction">{{ __('admin.streams.matches.tournament') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="scheduled_at" :sort="$sort" :direction="$direction">{{ __('admin.matches.scheduled_at') }}</x-admin.sortable-th>
                    <th class="px-4 py-3">{{ __('admin.streams.matches.channels') }}</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($matches as $match)
                    <tr class="border-b border-b-white/10 last:border-b-0 align-top">
                        <td class="px-4 py-3 text-white font-semibold whitespace-nowrap">
                            {{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }}
                            vs
                            {{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-300 whitespace-nowrap">
                            {{ $match->tournament->name ?? '—' }}
                            @if (\App\Support\MatchDisplay::rootPhaseName($match->tournamentPhase))
                                <span class="text-gray-500">— {{ \App\Support\MatchDisplay::rootPhaseName($match->tournamentPhase) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                            @if (\App\Support\MatchDisplay::isUnknownDate($match->scheduled_at))
                                {{ __('admin.matches.unknown_date') }}
                            @else
                                <span data-utc-datetime="{{ $match->scheduled_at->copy()->utc()->toIso8601String() }}">
                                    <span class="js-match-date">{{ $match->scheduled_at->format('Y-m-d') }}</span>
                                    <span class="js-match-time">{{ $match->scheduled_at->format('H:i') }}</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($match->streams as $stream)
                                    <span class="inline-flex items-center gap-1.5 bg-white/5 border border-white/10 rounded-full px-3 py-1 text-xs text-white">
                                        @svg($stream->icon(), 'w-3 h-3', ['aria-hidden' => 'true'])
                                        <span class="fi fi-{{ $stream->language_code === \App\Support\Countries::INTERNATIONAL ? 'un' : $stream->language_code }}"></span>
                                        {{ $stream->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('match.show', $match->id) }}" target="_blank" rel="noopener noreferrer"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.streams.matches.public_page') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.streams.matches.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $matches->links() }}
@endsection
