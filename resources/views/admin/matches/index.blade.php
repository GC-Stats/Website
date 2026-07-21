{{--
    GC-Stats — Admin: matches list (scoped to a tournament)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@php
    $matchRows = $matches->map(fn ($match) => [
        'id' => $match->id,
        'phase' => $match->tournamentPhase->name ?? '—',
        'round_name' => $match->round_name ?: '—',
        'team' => \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status).' vs '.\App\Support\MatchDisplay::teamShortName($match->teamB, $match->status)
            .(! is_null($match->team_a_score) ? ' ('.$match->team_a_score.' - '.$match->team_b_score.')' : ''),
        'status' => $match->status,
        'status_label' => __('admin.matches.status.'.$match->status),
        'date' => \App\Support\MatchDisplay::scheduledAt($match->scheduled_at),
        'date_ts' => optional($match->scheduled_at)->timestamp ?? 0,
        'manage_url' => route('admin.matches.show', [$tournament, $match]),
    ])->values();
@endphp

@section('title', __('admin.matches.title').' — '.$tournament->name)

@section('content')
    <a href="{{ route('admin.tournaments.show', $tournament) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $tournament->name }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ __('admin.matches.title') }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        @can('matches.create')
            <div class="lg:col-span-1 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-4">{{ __('admin.matches.create.title') }}</h2>
                @include('admin.matches._create-form', ['phases' => $phases, 'teams' => $teams, 'sticky' => $sticky])
            </div>
        @endcan

        <div class="{{ auth()->user()->can('matches.create') ? 'lg:col-span-2' : 'lg:col-span-3' }} bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl">
            <div class="p-4 border-b border-white/10">
                <form method="GET" action="{{ route('admin.matches.index', $tournament) }}" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">

                    <x-team-select class="w-44" name="team" :teams="$teams" :selected="$team" :placeholder="__('admin.matches.all_teams')" />

                    <select name="phase" class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                        <option value="">{{ __('admin.matches.all_phases') }}</option>
                        @foreach ($phases as $p)
                            <option value="{{ $p->id }}" @selected($phase == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>

                    <input type="text" name="round_name" value="{{ $round_name }}" placeholder="{{ __('admin.matches.round_name') }}"
                           class="w-32 h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

                    <input type="date" name="date" value="{{ $date }}"
                           class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

                    <select name="status" class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                        <option value="">{{ __('admin.matches.all_statuses') }}</option>
                        @foreach (['upcoming', 'live', 'finished'] as $s)
                            <option value="{{ $s }}" @selected($status === $s)>{{ __('admin.matches.status.'.$s) }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="h-[42px] font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.matches.filter') }}
                    </button>
                    <a href="{{ route('admin.matches.index', $tournament) }}"
                       class="h-[42px] inline-flex items-center font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        {{ __('admin.matches.reset') }}
                    </a>
                </form>
            </div>

            <div
                x-data="{
                        matches: {{ \Illuminate\Support\Js::from($matchRows) }},
                        sortCol: 'date_ts',
                        sortAsc: false,
                        sortBy(col) {
                            if (this.sortCol === col) this.sortAsc = !this.sortAsc;
                            else { this.sortCol = col; this.sortAsc = false; }

                            this.matches.sort((a, b) => {
                                let valA = a[this.sortCol] ?? 0;
                                let valB = b[this.sortCol] ?? 0;

                                if (!isNaN(valA) && !isNaN(valB)) {
                                    return this.sortAsc ? valA - valB : valB - valA;
                                }
                                return this.sortAsc
                                    ? String(valA).localeCompare(String(valB))
                                    : String(valB).localeCompare(String(valA));
                            });
                        }
                    }"
                class="overflow-x-auto"
            >
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                            @foreach ([['phase', 'admin.matches.phase'], ['round_name', 'admin.matches.round_name'], ['team', 'admin.matches.teams'], ['status', 'admin.matches.status_column'], ['date_ts', 'admin.matches.scheduled_at']] as [$col, $label])
                                <th class="px-4 py-3" @click="sortBy('{{ $col }}')">
                                    <span class="group inline-flex items-center gap-1 hover:text-white transition cursor-pointer select-none">
                                        {{ __($label) }}
                                        <span class="flex flex-col opacity-20 group-hover:opacity-100 transition-opacity" :class="sortCol === '{{ $col }}' ? 'opacity-100' : ''">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 mb-0.5" :class="sortCol === '{{ $col }}' && !sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 3l8 8h-16l8-8z" />
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" :class="sortCol === '{{ $col }}' && sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 21l-8-8h16l-8 8z" />
                                            </svg>
                                        </span>
                                    </span>
                                </th>
                            @endforeach
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="matches.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">—</td>
                            </tr>
                        </template>
                        <template x-for="match in matches" :key="match.id">
                            <tr class="border-b border-white/10 last:border-0">
                                <td class="px-4 py-3 text-xs font-bold uppercase text-gray-400" x-text="match.phase"></td>
                                <td class="px-4 py-3 text-xs uppercase text-gray-500" x-text="match.round_name"></td>
                                <td class="px-4 py-3 text-white font-semibold" x-text="match.team"></td>
                                <td class="px-4 py-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg"
                                          :class="match.status === 'finished' ? 'bg-white/5 text-gray-400' : (match.status === 'live' ? 'bg-red-500/10 text-red-400' : 'bg-green-500/10 text-green-400')"
                                          x-text="match.status_label">
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs" x-text="match.date"></td>
                                <td class="px-4 py-3 text-right">
                                    <a :href="match.manage_url"
                                       class="font-bold uppercase text-xs tracking-widest px-5 py-3 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10 inline-block">
                                        {{ __('admin.matches.manage') }}
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="p-4">
                {{ $matches->links() }}
            </div>
        </div>
    </div>
@endsection
