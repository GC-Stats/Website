{{--
    GC-Stats — Tournament statistics page

    Displays aggregated player statistics for the tournament: leader cards
    on the left (same layout as tournament/maps.blade.php's insights
    sidebar), the full sortable/filterable table with a column picker and a
    total/average toggle on the right.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('tournament.title.stats', ["tournament" => $tournament['name']]))

@section('content')
    @include('public.tournament.header')

    <div
        x-data="statsTable({{ json_encode($stats) }}, {{ json_encode($weapons) }}, 'tournament')"
        class="max-w-[1600px] mx-auto space-y-4"
    >
        <div class="border-b border-border-subtle pb-2 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                {{ __("tournament.stats.title", ["tournament" => $tournament['name']]) }}
            </h2>
        </div>

        <div class="group block mb-6">
            <div class="bg-black/40 backdrop-blur-md border border-white/10 rounded-xl p-3 shadow-2xl">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                    @if($phases->isNotEmpty())
                        <div class="flex items-center gap-4">
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

                    <form id="tournament-stats-filter-form" action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center gap-2 shrink-0 lg:ml-auto">
                        @foreach(request()->except(['start_date', 'end_date', 'agents', 'roles', 'maps']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        @include('public.partials.stats-filter-picker', ['formId' => 'tournament-stats-filter-form', 'name' => 'agents', 'label' => __('match.stats.filter_agent'), 'options' => $filterOptions['agents'], 'selected' => $selectedAgents])
                        @include('public.partials.stats-filter-picker', ['formId' => 'tournament-stats-filter-form', 'name' => 'roles', 'label' => __('match.stats.filter_role'), 'options' => $filterOptions['roles'], 'selected' => $selectedRoles])
                        @include('public.partials.stats-filter-picker', ['formId' => 'tournament-stats-filter-form', 'name' => 'maps', 'label' => __('match.stats.filter_map'), 'options' => $filterOptions['maps'], 'selected' => $selectedMaps])

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

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-3 space-y-6">
                @include('public.partials.stats-insights', ['insights' => $insights, 'namespace' => 'tournament.stats'])
            </div>

            <div class="col-span-12 lg:col-span-9 space-y-4">
                <div class="bg-bg-card rounded border border-gray-800 shadow-xl w-full">
                    <div class="relative" @click.outside="closeColPicker()">
                        <div class="flex items-center justify-between p-2 border-b border-gray-800/50 rounded-t overflow-hidden">
                            <button type="button" @click="colPickerOpen = !colPickerOpen"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-gray-400 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] rounded-md transition-colors">
                                <x-fas-sliders class="w-3 h-3" aria-hidden="true" />
                                {{ __('match.stats.columns') }}
                            </button>

                            <div class="flex bg-black/40 p-0.5 rounded-md border border-gray-800">
                                <button type="button" @click="setMode('avg')" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded transition-all" :class="mode === 'avg' ? 'bg-gc-yellow text-black' : 'text-gray-400 hover:text-gray-200'">{{ __('match.stats.mode_average') }}</button>
                                <button type="button" @click="setMode('total')" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded transition-all" :class="mode === 'total' ? 'bg-gc-yellow text-black' : 'text-gray-400 hover:text-gray-200'">{{ __('match.stats.mode_total') }}</button>
                            </div>
                        </div>

                        <div x-show="colPickerOpen" x-cloak x-transition
                             class="absolute left-2 top-10 z-40 w-56 bg-[#111214] border border-white/10 rounded-lg shadow-2xl p-2 space-y-0.5">
                            <div class="max-h-64 overflow-y-auto space-y-0.5">
                                <template x-for="col in allCols" :key="col.key">
                                    <template x-if="!col.key.startsWith('weapon_')">
                                        <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white/[0.04] cursor-pointer text-[11px] text-gray-300">
                                            <input type="checkbox" :checked="visibleCols.includes(col.key)" @change="toggleCol(col.key)"
                                                   class="w-3.5 h-3.5 rounded border-gray-700 bg-black/40 text-[var(--brand-yellow)] focus:ring-0 focus:ring-offset-0">
                                            <span x-text="col.name"></span>
                                        </label>
                                    </template>
                                </template>
                            </div>

                            @if($weapons)
                                <div class="relative group/weapon border-t border-white/10 pt-1 mt-1">
                                    <label class="flex items-center justify-between gap-2 px-2 py-1.5 rounded hover:bg-white/[0.04] cursor-pointer text-[11px] text-gray-300">
                                        <span class="flex items-center gap-2">
                                            {{ __('match.stats.weapon_kills') }}
                                            <span class="text-gray-600" x-text="'(' + allCols.filter(c => c.key.startsWith('weapon_') && visibleCols.includes(c.key)).length + ')'"></span>
                                        </span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </label>

                                    <div class="hidden group-hover/weapon:block absolute left-full top-0 w-44 bg-[#111214] border border-white/10 rounded-lg shadow-2xl p-1 space-y-0.5 max-h-72 overflow-y-auto z-50">
                                        @foreach($weapons as $weapon)
                                            <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white/[0.06] cursor-pointer text-[11px] text-gray-300">
                                                <input type="checkbox" :checked="visibleCols.includes({{ Js::from(\App\Services\UtilityStatsAggregator::weaponKey($weapon)) }})" @change="toggleCol({{ Js::from(\App\Services\UtilityStatsAggregator::weaponKey($weapon)) }})"
                                                       class="w-3.5 h-3.5 rounded border-gray-700 bg-black/40 text-[var(--brand-yellow)] focus:ring-0 focus:ring-offset-0">
                                                {{ $weapon }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div
                        x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
                        @mousedown="if(window.innerWidth < 768) { isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft; $el.classList.add('cursor-grabbing') }"
                        @mouseleave="isDown = false; $el.classList.remove('cursor-grabbing')"
                        @mouseup="isDown = false; $el.classList.remove('cursor-grabbing')"
                        @mousemove="if(!isDown) return; $event.preventDefault(); const x = $event.pageX - $el.offsetLeft; const walk = (x - startX) * 2; $el.scrollLeft = scrollLeft - walk;"
                        class="overflow-x-auto md:overflow-x-visible cursor-grab md:cursor-default select-none md:select-text no-scrollbar relative rounded-b"
                    >
                        <table class="w-full text-[11px] min-w-[650px] md:min-w-0 border-separate border-spacing-0">
                            <thead class="bg-black/20 text-gray-500 uppercase font-black tracking-tighter">
                                <tr>
                                    <th class="py-3 px-2 w-px text-left bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/50 shadow-[1px_0_0_0_#2a2a2a] md:shadow-none">{{ __("match.stats.agent_name") }}</th>

                                    <th @click="sortBy('player_handle')" class="p-3 text-center border-b border-gray-800/50 hover:text-white transition-colors cursor-pointer">{{ __("match.stats.player") }}</th>

                                    <th @click="sortBy('player_country_code')" class="p-3 text-center border-b border-gray-800/50 hover:text-white transition-colors cursor-pointer">{{ __("match.stats.nationality") }}</th>

                                    <template x-for="col in allCols" :key="col.key">
                                        <th x-show="visibleCols.includes(col.key)" @click="sortBy(col.key)" class="p-3 text-center border-b border-gray-800/50 hover:text-white transition-colors group cursor-pointer">
                                            <div class="flex items-center justify-center gap-1">
                                                <span x-text="col.name"></span>

                                                <div class="flex flex-col opacity-20 group-hover:opacity-100 transition-opacity" :class="sortCol === col.key ? 'opacity-100' : ''">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 mb-0.5" :class="sortCol === col.key && !sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 3l8 8h-16l8-8z" />
                                                    </svg>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" :class="sortCol === col.key && sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 21l-8-8h16l-8 8z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                <template x-for="stat in filteredStats" :key="stat.player_id">
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

                                        <td class="p-2 text-center border-b border-gray-800/30">
                                            <span class="fi shadow-sm inline-block"
                                                  :class="'fi-' + (stat.player_country_code ? stat.player_country_code.toLowerCase() : 'un')"
                                                  :aria-label="stat.player_country_code || ''"
                                                  role="img"></span>
                                        </td>

                                        <template x-for="col in allCols" :key="col.key">
                                            <td x-show="visibleCols.includes(col.key)" class="p-2 text-center border-b border-gray-800/30"
                                                :class="col.key === 'kast' ? (val(stat, 'kast') >= 75 ? 'text-green-500' : 'text-gray-500') : 'text-gray-300'"
                                                x-text="formatVal(stat, col.key)">
                                            </td>
                                        </template>
                                    </tr>
                                </template>

                                <tr x-show="filteredStats.length === 0">
                                    <td colspan="21" class="p-8 text-center text-gray-600 uppercase font-black tracking-widest">{{ __("tournament.stats.no_data") }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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

    @include('public.partials.stats-table-script')
@endsection
