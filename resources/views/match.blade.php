{{--
    GC-Stats — Match detail page

    Displays a single match: tournament header, teams, score, map vetoes,
    per-map results and player statistics.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@php
    $teamAName = $match['team_a_data']['name'] ?? ($match['status'] == 'finished' ? 'BYE' : 'TBD');
    $teamBName = $match['team_b_data']['name'] ?? ($match['status'] == 'finished' ? 'BYE' : 'TBD');
@endphp

@section('title', __("match.title", ["teamA" => $teamAName, "teamB" => $teamBName]))

@section('content')
    <section class="mx-auto w-full max-w-7xl py-8 px-4">
        <div class="relative mb-6 flex flex-col items-center">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-full h-[1px] bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
            </div>

            <a href="{{ isset($match['tournament']['id']) ? route('tournaments.show', [$match['tournament']['id'], str($match['tournament']['name'] ?? $match['tournament_name'] ?? '')->slug()]) : '#' }}"
               class="group relative bg-bg-main px-8 py-1 transition-all">

                <div class="flex flex-col items-center gap-0.5">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em] group-hover:text-[var(--brand-yellow)] transition-colors">
                        {{ $match['tournament']['name'] ?? $match['tournament_name'] ?? 'Tournament' }}
                    </span>

                    <span class="text-[9px] font-bold text-[var(--brand-yellow)]/60 uppercase tracking-widest">
                        {{ $match['tournament_phase']['name'] ?? $match['phase_name'] }}
                    </span>
                </div>

                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-[1px] bg-[var(--brand-yellow)] transition-all duration-300 group-hover:w-1/2 opacity-50"></div>
            </a>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-b from-white/[0.03] to-transparent border border-white/5 rounded-2xl p-4 md:p-8 shadow-2xl backdrop-blur-sm">
            <div class="absolute inset-0 opacity-[0.02] pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>

            <div class="relative flex flex-col md:flex-row items-center justify-between gap-8 md:gap-4">

                <a href="{{ $match['team_a_id'] ? route('teams.show', [$match['team_a_id'], str($teamAName)->slug()]) : '#' }}"
                   class="flex flex-col md:flex-row items-center gap-4 md:gap-6 flex-1 min-w-0 group justify-center md:justify-end">

                    <div class="relative order-2 md:order-1 text-center md:text-right min-w-0">
                        <h3 class="font-black text-xl md:text-2xl uppercase italic text-white group-hover:text-[var(--brand-yellow)] transition-colors tracking-tight leading-none truncate">
                            {{ $teamAName }}
                        </h3>
                    </div>

                    <div class="relative order-1 md:order-2 shrink-0">
                        <div class="absolute inset-0 bg-[var(--brand-yellow)]/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <img src="{{ $match['team_a_data']['logo'] ?? asset('storage/images/default-team.webp') }}"
                             alt="{{ $teamAName }}"
                             class="relative w-14 h-14 md:w-20 md:h-20 object-contain transition-transform duration-500 group-hover:scale-110">
                    </div>
                </a>

                <div class="flex flex-col items-center shrink-0 z-10 px-4">
                    @if(!empty($match['patch']))
                        <span class="mb-4 text-[8px] font-medium text-gray-600 uppercase tracking-widest">
                            {{ __('match.patch', ['patch' => $match['patch']]) }}
                        </span>
                    @endif

                    <div class="relative group">
                        <div class="absolute -inset-4 bg-white/[0.02] rounded-full blur-2xl"></div>

                        <div class="relative flex items-center justify-center gap-4 bg-black/60 backdrop-blur-xl border border-white/10 px-8 py-4 rounded-2xl shadow-2xl overflow-hidden">
                            @if($match["status"] == "finished")
                                <span class="sr-only">{{ __('match.score_label', ['teamA' => $teamAName, 'scoreA' => $match['team_a_score'], 'scoreB' => $match['team_b_score'], 'teamB' => $teamBName]) }}</span>
                                <span class="text-4xl md:text-5xl font-black {{ $match["team_a_score"] > $match["team_b_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter" aria-hidden="true">{{ $match["team_a_score"] == -1 ? 'FF' : $match["team_a_score"] }}</span>
                                <div class="w-[1px] h-8 bg-white/10" aria-hidden="true"></div>
                                <span class="text-4xl md:text-5xl font-black {{ $match["team_b_score"] > $match["team_a_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter" aria-hidden="true">{{ $match["team_b_score"] == -1 ? 'FF' : $match["team_b_score"] }}</span>
                            @elseif($match["status"] == "upcoming")
                                <span class="text-4xl md:text-5xl font-black text-white tracking-tighter" aria-label="{{ __('match.upcoming') }}">VS</span>
                            @else
                                <div class="flex flex-col items-center" role="status" aria-live="polite">
                                    <span class="text-sm font-black text-green-500 animate-pulse tracking-[0.3em] mb-1">LIVE</span>
                                    <span class="sr-only">{{ __('match.score_label', ['teamA' => $teamAName, 'scoreA' => $match['team_a_score'], 'scoreB' => $match['team_b_score'], 'teamB' => $teamBName]) }}</span>
                                    <div class="flex items-center gap-2" aria-hidden="true">
                                        <span class="text-4xl md:text-5xl font-black {{ $match["team_a_score"] > $match["team_b_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter">{{ $match["team_a_score"] == -1 ? 'FF' : $match["team_a_score"] }}</span>
                                        <div class="w-[1px] h-8 bg-white/10"></div>
                                        <span class="text-4xl md:text-5xl font-black {{ $match["team_b_score"] > $match["team_a_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter">{{ $match["team_b_score"] == -1 ? 'FF' : $match["team_b_score"] }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(\App\Helpers\PivotDate::isUnknown($match['scheduled_at'] ?? null))
                        <div class="mt-4 flex flex-col items-center gap-1">
                            <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">
                                {{ __('match.unknown_date') }}
                            </span>
                        </div>
                    @else
                        <div class="mt-4 flex flex-col items-center gap-1" data-utc-datetime="{{ \Carbon\Carbon::parse($match['scheduled_at'], 'UTC')->toIso8601String() }}">
                            <span class="js-match-date text-[10px] font-black text-white/40 uppercase tracking-widest">
                                {{ \Carbon\Carbon::parse($match['scheduled_at'])->translatedFormat('d M Y') }}
                            </span>
                            <span class="js-match-time text-[11px] font-black text-[var(--brand-yellow)] tracking-tighter">
                                {{ \Carbon\Carbon::parse($match['scheduled_at'])->format('H:i') }}
                            </span>
                        </div>
                    @endif
                </div>

                <a href="{{ $match['team_b_id'] ? route('teams.show', [$match['team_b_id'], str($teamBName)->slug()]) : '#' }}"
                   class="flex flex-col md:flex-row items-center gap-4 md:gap-6 flex-1 min-w-0 group justify-center md:justify-start">

                    <div class="relative shrink-0">
                        <div class="absolute inset-0 bg-[var(--brand-yellow)]/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <img src="{{ $match['team_b_data']['logo'] ?? asset('storage/images/default-team.webp') }}"
                             alt="{{ $teamBName }}"
                             class="relative w-14 h-14 md:w-20 md:h-20 object-contain transition-transform duration-500 group-hover:scale-110">
                    </div>

                    <div class="text-center md:text-left min-w-0">
                        <h3 class="font-black text-xl md:text-2xl uppercase italic text-white group-hover:text-[var(--brand-yellow)] transition-colors tracking-tight leading-none truncate">
                            {{ $teamBName }}
                        </h3>
                    </div>
                </a>
            </div>

            <div class="mt-10 -mx-4 md:-mx-8">
                <div class="flex items-center justify-center gap-4 mb-8">
                    <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent to-white/10"></div>
                    <span class="text-[8px] font-black text-gray-600 uppercase tracking-[0.5em]">{{ __('match.veto') }}</span>
                    <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent to-white/10"></div>
                </div>

                <div class="flex flex-wrap justify-center gap-x-12 gap-y-8 px-6">
                    @php
                        $bans = collect($match['map_bans'])->sortBy('order');
                        $hasBans = $bans->where('type', 'ban')->isNotEmpty();
                    @endphp

                    @if($hasBans)
                        @foreach(collect($match['map_bans'])->sortBy('order') as $ban)
                            <div class="flex flex-col items-center group">
                                <div class="mb-4">
                                <span class="text-[7px] font-black uppercase tracking-[0.2em] px-2 py-1 border-b-2
                                    {{ $ban['type'] == 'ban' ? 'text-red-500 border-red-500/40' : ($ban['type'] == 'decider' ? 'text-blue-400 border-blue-400/40' : 'text-green-500 border-green-500/40') }}">
                                    {{ $ban['type'] }}
                                </span>
                                </div>

                                <span class="text-[9px] font-black text-gray-500 uppercase tracking-tight mb-2 {{ $ban['type'] == 'decider' ? 'invisible' : '' }}">
                                    {{ $ban['team']['short_name'] ?? Str::limit($ban['team']['name'] ?? '', 6, '') }}
                                </span>

                                <div class="flex flex-col items-center">
                                    <div class="flex flex-col items-center">
                                        <span class="text-xs font-black text-white uppercase italic tracking-[0.1em] mb-1">
                                            {{ $ban['map_name'] }}
                                        </span>

                                        @if(in_array($ban['type'], ['pick', 'decider']) && !empty($ban['side']))
                                            <span class="text-[7px] font-black uppercase tracking-[0.2em] text-gray-500">
                                                {{ $ban['side_picked_by']['short_name'] ?? Str::limit($ban['side_picked_by']['name'] ?? '', 6, '') }}
                                            </span>
                                            <span class="text-[7px] font-black uppercase tracking-[0.2em] text-gray-400">
                                                {{ $ban['side'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <span class="text-[9px] text-gray-700 italic uppercase tracking-[0.2em]">{{ __("match.no_veto") }}</span>
                    @endif
                </div>
            </div>
        </div>

        @if(count($match['game_maps']) > 0)
            <div x-data="{ activeMap: {{ $match['best_of'] == 1 ? $match['game_maps'][0]['id'] : 0 }} }" class="mt-12">
                <div class="flex flex-wrap gap-3 mb-10 justify-center" role="tablist" aria-label="{{ __('match.map_selector') }}">
                    @if($match['best_of'] != 1)
                        <button @click="activeMap = 0"
                                role="tab"
                                :aria-selected="(activeMap === 0).toString()"
                                :aria-controls="'map-panel-all'"
                                :class="activeMap === 0 ? 'bg-[var(--brand-yellow)] text-black shadow-[0_0_15px_rgba(var(--brand-yellow-rgb),0.3)]' : 'bg-white/5 text-gray-400 hover:bg-white/10'"
                                class="px-8 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-full transition-all duration-300">
                            {{ __("match.all_maps") }}
                        </button>
                    @endif
                    @foreach($match['game_maps'] as $map)
                        <button @click="activeMap = {{ $map['id'] }}"
                                role="tab"
                                :disabled="{{ $map['team_a_score'] }} === -1 || {{ $map['team_b_score'] }} === -1"
                                :aria-selected="(activeMap === {{ $map['id'] }}).toString()"
                                :aria-controls="'map-panel-{{ $map['id'] }}'"
                                :class="activeMap === {{ $map['id'] }} ? 'bg-[var(--brand-yellow)] text-black shadow-[0_0_15px_rgba(var(--brand-yellow-rgb),0.3)]' : 'bg-white/5 text-gray-400 hover:bg-white/10'"
                                class="group px-6 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-full transition-all duration-300 flex items-center disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white/5">
                            {{ $map['map_name'] }}
                            @if($map['team_a_score'] != -1 && $map['team_b_score'] != -1)
                                <span class="ml-3 px-2 py-0.5 rounded-full bg-black/20 group-hover:bg-black/40 transition-colors text-[9px]" aria-label="{{ $map['team_a_score'] }}-{{ $map['team_b_score'] }}">
                                    {{ $map['team_a_score']."-".$map['team_b_score'] }}
                                </span>
                            @endif

                        </button>
                    @endforeach
                </div>

                <template x-if="activeMap === 0">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 animate-fadeIn">
                        @include('partials.team-stats-table', ['stats' => $totalA, 'teamName' => $match['team_a_data']['name'], 'multiple' => true])
                        @include('partials.team-stats-table', ['stats' => $totalB, 'teamName' => $match['team_b_data']['name'], 'multiple' => true, 'reverse' => true])
                    </div>
                </template>

                @if($match['status'] != "upcoming")
                    @foreach($match['game_maps'] as $map)
                        <div x-show="activeMap === {{ $map['id'] }}" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-12">

                            @if(!empty($map["rounds"]))
                                @include('partials.round-history', ['map' => $map])
                            @endif

                            @php
                                $mStatsA = $map['stats_a'];
                                $mStatsB = $map['stats_b'];
                            @endphp

                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                                @include('partials.team-stats-table', ['multiple' => false, 'stats' => $mStatsA, 'teamName' => $match['team_a_data']['name']])
                                @include('partials.team-stats-table', ['multiple' => false, 'stats' => $mStatsB, 'teamName' => $match['team_b_data']['name'], 'reverse' => true])
                            </div>

                            <div class="grid grid-cols-12 gap-6">
                                @if($map['eco_summary']['team_a']['eco']['total'] > 0)
                                    <div class="col-span-12 lg:col-span-2 order-2 lg:order-1">
                                        <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-4 h-full">
                                            <h4 class="text-[8px] font-black uppercase text-gray-500 mb-6 text-center tracking-[0.3em]">
                                                {{ __("match.economy", ["team" => Str::limit($match['team_a_data']['name'], 8)]) }}
                                            </h4>

                                            <div class="flex flex-col gap-2">
                                                @foreach($map['eco_summary']['team_a'] as $eco)
                                                    <div class="group flex items-center justify-between bg-black/40 rounded-xl border border-white/5 p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300">

                                                        <div class="flex items-center gap-3">
                                                        <span class="text-[8px] text-gray-400 uppercase font-black tracking-wider group-hover:text-gray-200 transition-colors">
                                                            {{ $eco['label'] }}
                                                        </span>
                                                        </div>

                                                        <div class="flex flex-col items-end">
                                                        <span class="text-white font-black text-[11px] leading-none">
                                                            {{ $eco['win'] }}<span class="text-gray-600 mx-0.5 text-[9px]">/</span>{{ $eco['total'] }}
                                                        </span>
                                                            <div class="mt-1 w-8 h-[2px] bg-white/5 rounded-full overflow-hidden">
                                                                <div class="h-full bg-[var(--brand-yellow)] opacity-40 group-hover:opacity-100 transition-all"
                                                                     style="width: {{ $eco['total'] > 0 ? ($eco['win'] / $eco['total']) * 100 : 0 }}%">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($map['performance']))
                                    <div class="col-span-12 lg:col-span-8 order-1 lg:order-2 bg-[#0d0d0d] rounded-2xl border border-white/5 shadow-2xl overflow-hidden">
                                        <div class="bg-white/[0.03] px-6 py-4 border-b border-white/5">
                                            <div class="flex items-center justify-center gap-4">
                                                <span class="text-[9px] font-black uppercase tracking-[0.4em] text-gray-400">
                                                    {{ __('match.performance') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="overflow-x-auto no-scrollbar">
                                            <table class="w-full text-[10px] border-separate border-spacing-0">
                                                <caption class="sr-only">{{ __('match.performance_caption', ['teamA' => $teamAName, 'teamB' => $teamBName]) }}</caption>
                                                <thead>
                                                <tr class="text-gray-500 uppercase font-black tracking-widest bg-white/[0.01]">
                                                    <th scope="col" class="p-4 text-left border-b border-white/5">{{ __('match.stats.player') }}</th>
                                                    @foreach(['SHERIFF','2K','3K','4K','5K'] as $h)
                                                        <th scope="col" class="p-4 border-b border-white/5">{{ $h }}</th>
                                                    @endforeach
                                                    @foreach(['5K','4K','3K','2K','SHERIFF'] as $h)
                                                        <th scope="col" class="p-4 border-b border-white/5 bg-black/20">{{ $h }}</th>
                                                    @endforeach
                                                    <th scope="col" class="p-4 text-right border-b border-white/5 bg-black/20">{{ __('match.stats.player') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-white/[0.03]">
                                                @for($i = 0; $i < 5; $i++)
                                                    @php
                                                        $pA = $mStatsA[$i] ?? null;
                                                        $pB = $mStatsB[$i] ?? null;
                                                        $pfA = $pA ? ($map['performance'][$pA['player_id']] ?? null) : null;
                                                        $pfB = $pB ? ($map['performance'][$pB['player_id']] ?? null) : null;
                                                    @endphp
                                                    <tr class="group hover:bg-white/[0.02] transition-colors">
                                                        <td class="p-4 font-black text-white italic tracking-tighter">{{ $pA['player']['handle'] ?? '-' }}</td>

                                                        @foreach(['sheriff_kills','2k','3k','4k','5k'] as $k)
                                                            <td class="p-4 text-center">
                                                                <span class="{{ ($pfA[$k] ?? 0) > 0 ? 'text-[var(--brand-yellow)] font-black drop-shadow-[0_0_8px_rgba(var(--brand-yellow-rgb),0.4)]' : 'text-gray-700 font-medium' }}">
                                                                    {{ $pfA[$k] ?? 0 }}
                                                                </span>
                                                            </td>
                                                        @endforeach

                                                        @foreach(['5k','4k','3k','2k','sheriff_kills'] as $k)
                                                            <td class="p-4 text-center bg-black/10">
                                                                <span class="{{ ($pfB[$k] ?? 0) > 0 ? 'text-[var(--brand-yellow)] font-black drop-shadow-[0_0_8px_rgba(var(--brand-yellow-rgb),0.4)]' : 'text-gray-700 font-medium' }}">
                                                                    {{ $pfB[$k] ?? 0 }}
                                                                </span>
                                                            </td>
                                                        @endforeach

                                                        <td class="p-4 font-black text-right text-white italic tracking-tighter bg-black/10">{{ $pB['player']['handle'] ?? '-' }}</td>
                                                    </tr>
                                                @endfor
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                @if($map['eco_summary']['team_b']['eco']['total'] > 0)
                                        <div class="col-span-12 lg:col-span-2 order-3">
                                            <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-4 h-full">
                                                <h4 class="text-[8px] font-black uppercase text-gray-500 mb-6 text-center tracking-[0.3em]">
                                                    {{ __("match.economy", ["team" => Str::limit($match['team_b_data']['name'], 8)]) }}
                                                </h4>

                                                <div class="flex flex-col gap-2">
                                                    @foreach($map['eco_summary']['team_b'] as $eco)
                                                        <div class="group flex items-center justify-between bg-black/40 rounded-xl border border-white/5 p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300">

                                                            <div class="flex items-center gap-3">
                                                        <span class="text-[8px] text-gray-400 uppercase font-black tracking-wider group-hover:text-gray-200 transition-colors">
                                                            {{ $eco['label'] }}
                                                        </span>
                                                            </div>

                                                            <div class="flex flex-col items-end">
                                                        <span class="text-white font-black text-[11px] leading-none">
                                                            {{ $eco['win'] }}<span class="text-gray-600 mx-0.5 text-[9px]">/</span>{{ $eco['total'] }}
                                                        </span>
                                                                <div class="mt-1 w-8 h-[2px] bg-white/5 rounded-full overflow-hidden">
                                                                    <div class="h-full bg-[var(--brand-yellow)] opacity-40 group-hover:opacity-100 transition-all"
                                                                         style="width: {{ $eco['total'] > 0 ? ($eco['win'] / $eco['total']) * 100 : 0 }}%">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        <style>
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
        </style>
    </section>
@endsection
