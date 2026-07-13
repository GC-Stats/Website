{{--
    GC-Stats — Player statistics page

    Displays detailed aggregated statistics for the player (ACS, K/D/A,
    ADR, KAST, headshot %, etc.), with filters.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('player.title.stats', ["player" => $player['handle']]))

@section('content')
    @include('player.header')

    <div class="max-w-6xl mx-auto space-y-4">
        <div class="border-b border-border-subtle pb-2 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                {{ __("player.stats.title", ["player" => $player['handle']]) }}
            </h2>
        </div>

        <div class="space-y-4 select-none" style="user-select: none;">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-bg-card p-4 rounded border border-gray-800 shadow-xl select-none">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] uppercase font-black text-gray-500 tracking-wider">{{ __("player.stats.period") }}</span>
                    <div class="flex bg-black/40 p-1 rounded-md border border-gray-800">
                        <a href="{{ request()->fullUrlWithQuery(['days' => 0, 'start_date' => null, 'end_date' => null]) }}"
                           class="px-3 py-1 text-[11px] font-bold rounded transition-all {{ (request('days', 0) == 0 && !request('start_date')) ? 'bg-gc-yellow text-white shadow-lg' : 'text-gray-400 hover:text-gray-200' }}">
                            All time
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['days' => 30, 'start_date' => null, 'end_date' => null]) }}"
                           class="px-3 py-1 text-[11px] font-bold rounded transition-all {{ (request('days') == 30 && !request('start_date')) ? 'bg-gc-yellow text-white shadow-lg' : 'text-gray-400 hover:text-gray-200' }}">
                            30J
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['days' => 60, 'start_date' => null, 'end_date' => null]) }}"
                           class="px-3 py-1 text-[11px] font-bold rounded transition-all {{ (request('days') == 60) ? 'bg-gc-yellow text-white shadow-lg' : 'text-gray-400 hover:text-gray-200' }}">
                            60J
                        </a>
                    </div>
                </div>

                <form action="{{ url()->current() }}" method="GET" class="flex items-center" aria-label="{{ __('player.stats.date_filter') }}">
                    @foreach(request()->except(['start_date', 'end_date', 'days']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                        <div class="flex items-center bg-black/40 rounded-md border border-gray-800 overflow-hidden select-none">
                            <label for="start_date" class="sr-only">{{ __('player.stats.start_date') }}</label>
                            <input type="date"
                                   id="start_date"
                                   name="start_date"
                                   value="{{ request('start_date') }}"
                                   class="bg-transparent border-none text-[11px] text-gray-300 w-28 py-1 px-2 focus:outline-none">

                            <span class="text-gray-700 font-bold px-1 pointer-events-none whitespace-nowrap" aria-hidden="true">–</span>

                            <label for="end_date" class="sr-only">{{ __('player.stats.end_date') }}</label>
                            <input type="date"
                                   id="end_date"
                                   name="end_date"
                                   value="{{ request('end_date') }}"
                                   class="bg-transparent border-none text-[11px] text-gray-300 w-28 py-1 px-2 focus:outline-none">
                        </div>
                    <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-gray-300 p-1.5 rounded transition-colors ml-2" aria-label="{{ __('player.stats.filter_submit') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Ton Tableau existant -->
            <div class="bg-bg-card rounded border border-gray-800 shadow-xl w-full overflow-hidden">
                <div
                    x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
                    @mousedown="if(window.innerWidth < 768) { isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft; $el.classList.add('cursor-grabbing') }"
                    @mouseleave="isDown = false; $el.classList.remove('cursor-grabbing')"
                    @mouseup="isDown = false; $el.classList.remove('cursor-grabbing')"
                    @mousemove="if(!isDown) return; $event.preventDefault(); const x = $event.pageX - $el.offsetLeft; const walk = (x - startX) * 2; $el.scrollLeft = scrollLeft - walk;"
                    class="overflow-x-auto md:overflow-x-visible cursor-grab md:cursor-default select-none md:select-text no-scrollbar relative"
                >
                    <table class="w-full text-[11px] min-w-[650px] md:min-w-0 border-separate border-spacing-0">
                        <thead class="bg-black/20 text-gray-500 uppercase font-black tracking-tighter">
                        <tr>
                            <th scope="col" class="p-3 w-12 text-center bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/50 shadow-[1px_0_0_0_#2a2a2a] md:shadow-none">{{ __("match.stats.agent_name") }}</th>
                            <th scope="col" class="p-3 text-center border-b border-gray-800/50">{{ __("match.stats.acs") }}</th>
                            <th scope="col" class="p-3 text-center text-white border-b border-gray-800/50">{{ __("match.stats.kills") }}/{{ __("match.stats.deaths") }}/{{ __("match.stats.assists") }}</th>
                            <th scope="col" class="p-3 text-center border-b border-gray-800/50">{{ __("match.stats.adr") }}</th>
                            <th scope="col" class="p-3 text-center border-b border-gray-800/50">{{ __("match.stats.kast_percentage") }}</th>
                            <th scope="col" class="p-3 text-center border-b border-gray-800/50">{{ __("match.stats.first_kills") }}/{{ __("match.stats.first_deaths") }}</th>
                            <th scope="col" class="p-3 text-center border-b border-gray-800/50">{{ __("match.stats.headshot_percentage") }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                        @forelse($stats as $stat)
                            <tr class="group transition-colors hover:bg-white/[0.03]">
                                <td class="p-2 text-center bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/30 group-hover:bg-bg-card-hover transition-colors shadow-[1px_0_0_0_#2a2a2a] md:shadow-none">
                                    <img src="{{ asset('storage/agents/' . strtolower(str_replace('/', '', $stat['agent_name'])) . '.webp') }}" alt="{{ $stat['agent_name'] }}" class="w-8 h-8 rounded border border-gray-700 bg-bg-main mx-auto">
                                </td>
                                <td class="p-2 text-center font-mono font-bold text-gray-400 border-b border-gray-800/30">{{ number_format($stat['avg_acs'], 1) }}</td>
                                <td class="p-2 text-center whitespace-nowrap border-b border-gray-800/30"><span class="text-white font-bold">{{ number_format($stat['avg_kills'], 1) }}</span><span class="text-gray-600"> / </span><span class="text-red-500/80">{{ number_format($stat['avg_deaths'], 1) }}</span><span class="text-gray-600"> / </span><span class="text-gray-500">{{ number_format($stat['avg_assists'], 1) }}</span></td>
                                <td class="p-2 text-center text-gray-300 border-b border-gray-800/30">{{ number_format($stat['avg_adr'], 1) }}</td>
                                <td class="p-2 text-center {{ $stat['avg_kast'] >= 75 ? 'text-green-500' : 'text-gray-500' }} border-b border-gray-800/30">{{ round($stat['avg_kast']) }}%</td>
                                <td class="p-2 text-center whitespace-nowrap border-b border-gray-800/30"><span class="text-green-500/70">{{ number_format($stat['avg_first_kills'], 1) }}</span><span class="text-gray-700"> - </span><span class="text-red-500/70">{{ number_format($stat['avg_first_deaths'], 1) }}</span></td>
                                <td class="p-2 text-center text-gray-500 font-bold border-b border-gray-800/30">{{ round($stat['avg_hs']) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-600 uppercase font-black tracking-widest">
                                    {{ __("player.stats.no_data") }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>

            td.sticky, th.sticky {
                background-clip: padding-box;
            }

            .no-scrollbar {
                -webkit-user-select: none; /* Safari */
                -ms-user-select: none; /* IE 10+ */
                user-select: none; /* Standard */
            }
        </style>
    </div>
@endsection
