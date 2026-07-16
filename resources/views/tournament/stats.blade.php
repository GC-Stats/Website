{{--
    GC-Stats — Tournament statistics page

    Displays aggregated player/team statistics for the tournament, with
    filters and sorting.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('tournament.title.stats', ["tournament" => $tournament['name']]))

@section('content')
    @include('tournament.header')

    <div class="max-w-6xl mx-auto space-y-4">
        <div class="border-b border-border-subtle pb-2 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                {{ __("tournament.stats.title", ["tournament" => $tournament['name']]) }}
            </h2>
        </div>

        <div class="group block mb-6">
            <div class="bg-black/40 backdrop-blur-md border border-white/10 rounded-xl p-3 shadow-2xl">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                    @if($phases->isNotEmpty())
                        <div class="flex items-center gap-4 flex-1">
                            <span class="text-[9px] uppercase font-black text-gray-500 tracking-[0.2em] shrink-0">
                                {{ __("tournament.stats.phase") }}
                            </span>

                            <div class="flex flex-wrap bg-white/[0.03] p-1 rounded-lg border border-white/5 gap-1">
                                <a href="{{ request()->fullUrlWithQuery(['phase_id' => null]) }}"
                                   class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md transition-all duration-300
                                    {{ !$selectedPhase ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                    {{ __("tournament.stats.all_phases") }}
                                </a>

                                @foreach($phases as $phaseOption)
                                    <a href="{{ request()->fullUrlWithQuery(['phase_id' => $phaseOption['id']]) }}"
                                       class="px-4 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-md transition-all duration-300
                                        {{ $selectedPhase == $phaseOption['id'] ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">
                                        {{ $phaseOption['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2 shrink-0">
                        @foreach(request()->except(['start_date', 'end_date']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <div class="flex items-center bg-white/[0.03] rounded-lg border border-white/5 focus-within:border-[var(--brand-yellow)]/30 transition-all overflow-hidden">
                            <x-fas-clock class="w-3.5 h-3.5 inline-block text-gray-600 ml-3 mr-1 pointer-events-none" aria-hidden="true" />

                            <input type="date"
                                   name="start_date"
                                   value="{{ request('start_date') }}"
                                   class="bg-transparent border-none text-[10px] font-black uppercase text-gray-300 w-32 py-2 px-2 pr-2 focus:outline-none focus:ring-0 [color-scheme:dark] cursor-pointer">

                            <span class="text-gray-700 font-black select-none">–</span>

                            <input type="date"
                                   name="end_date"
                                   value="{{ request('end_date') }}"
                                   class="bg-transparent border-none text-[10px] font-black uppercase text-gray-300 w-32 py-2 px-2 pr-2 focus:outline-none focus:ring-0 [color-scheme:dark] cursor-pointer">
                        </div>

                        <button type="submit" class="bg-[var(--brand-yellow)] hover:scale-105 text-black p-2.5 rounded-lg transition-all active:scale-95 shadow-[0_0_15px_rgba(255,215,0,0.1)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div
                x-data="{
                        stats: {{ json_encode($stats) }},
                        sortCol: 'avg_acs',
                        sortAsc: false,
                        sortBy(col) {
                            if (this.sortCol === col) this.sortAsc = !this.sortAsc;
                            else { this.sortCol = col; this.sortAsc = false; }

                            this.stats.sort((a, b) => {
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
                class="bg-bg-card rounded border border-gray-800 shadow-xl w-full overflow-hidden"
            >
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
                                <th class="py-3 px-2 w-px text-left bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/50 shadow-[1px_0_0_0_#2a2a2a] md:shadow-none">{{ __("match.stats.agent_name") }}</th>

                                @php
                                    $tableItems = [
                                        ['name' =>  __("match.stats.player"), 'element' => 'player_handle'],
                                        ['name' =>  __("match.stats.acs"), 'element' => 'avg_acs'],
                                        ['name' =>  __("match.stats.kills"), 'element' => 'avg_kills'],
                                        ['name' =>  __("match.stats.deaths"), 'element' => 'avg_deaths'],
                                        ['name' =>  __("match.stats.assists"), 'element' => 'avg_assists'],

                                        ['name' =>  __("match.stats.adr"), 'element' => 'avg_adr'],
                                        ['name' =>  __("match.stats.kast_percentage"), 'element' => 'avg_kast'],
                                        ['name' =>  __("match.stats.first_kills"), 'element' => 'avg_first_kills'],
                                        ['name' =>  __("match.stats.first_deaths"), 'element' => 'avg_first_deaths'],
                                        ['name' =>  __("match.stats.headshot_percentage"), 'element' => 'avg_hs'],
                                    ];
                                @endphp

                                @foreach($tableItems as $item)
                                    <th @click="sortBy('{{ $item["element"] }}')" class="p-3 text-center border-b border-gray-800/50 hover:text-white transition-colors group cursor-pointer">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>{{ $item["name"] }}</span>

                                            <div class="flex flex-col opacity-20 group-hover:opacity-100 transition-opacity" :class="sortCol === '{{ $item["element"] }}' ? 'opacity-100' : ''">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 mb-0.5" :class="sortCol === '{{ $item["element"] }}' && !sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 3l8 8h-16l8-8z" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" :class="sortCol === '{{ $item["element"] }}' && sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 21l-8-8h16l-8 8z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            <template x-for="stat in stats" :key="stat.player_id">
                                <tr class="group transition-colors hover:bg-white/[0.03]">
                                    <td class="py-2 px-2 w-px text-left bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/30">
                                        <div class="inline-flex -space-x-2"
                                             :style="`width: ${28 + (stat.played_agents.length - 1) * 20}px`">
                                            <template x-for="agent in stat.played_agents">
                                                <img :src="'/storage/agents/' + agent.toLowerCase().replaceAll('/','') + '.webp'"
                                                     class="w-7 h-7 rounded-full border border-gray-900 bg-bg-main shrink-0"
                                                     loading="lazy"
                                                     :alt="agent">
                                            </template>
                                        </div>
                                    </td>

                                    <td class="p-2 font-bold text-center text-white uppercase sticky left-0 z-30 bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/30 shadow-[1px_0_0_0_#2a2a2a] md:shadow-none">
                                        <a :href="'/player/' + stat.player_id + '/' + (stat.player_handle || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')" x-text="stat.player_handle || '---'"></a>
                                    </td>

                                    <td class="p-2 text-center font-mono font-bold text-gray-400 border-b border-gray-800/30"
                                        x-text="Number(stat.avg_acs).toFixed(1)">
                                    </td>

                                    <td class="p-2 text-center text-gray-300 border-b border-gray-800/30"
                                        x-text="Number(stat.avg_kills).toFixed(1)">
                                    </td>

                                    <td class="p-2 text-center text-gray-300 border-b border-gray-800/30"
                                        x-text="Number(stat.avg_deaths).toFixed(1)">
                                    </td>

                                    <td class="p-2 text-center text-gray-300 border-b border-gray-800/30"
                                        x-text="Number(stat.avg_assists).toFixed(1)">
                                    </td>

                                    <td class="p-2 text-center text-gray-300 border-b border-gray-800/30"
                                        x-text="Number(stat.avg_adr).toFixed(1)">
                                    </td>

                                    <td class="p-2 text-center border-b border-gray-800/30"
                                        :class="stat.avg_kast >= 75 ? 'text-green-500' : 'text-gray-500'"
                                        x-text="Math.round(stat.avg_kast) + '%'">
                                    </td>

                                    <td class="p-2 text-center text-gray-500 font-bold border-b border-gray-800/30"
                                        x-text="Number(stat.avg_first_kills).toFixed(1)">
                                    </td>

                                    <td class="p-2 text-center text-gray-500 font-bold border-b border-gray-800/30"
                                        x-text="Number(stat.avg_first_deaths).toFixed(1)">
                                    </td>

                                    <!-- HS -->
                                    <td class="p-2 text-center text-gray-500 font-bold border-b border-gray-800/30"
                                        x-text="Math.round(stat.avg_hs) + '%'">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>

            td.sticky, th.sticky {
                background-clip: padding-box;
            }

            @media (max-width: 767px) {
                .no-scrollbar {
                    -webkit-user-select: none; /* Safari */
                    -ms-user-select: none; /* IE 10+ */
                    user-select: none; /* Standard */
                }
            }
        </style>
    </div>
@endsection
