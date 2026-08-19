{{--
    GC-Stats — Player statistics page

    Displays detailed aggregated per-agent statistics for the player (ACS,
    K/D/A, ADR, KAST, headshot %, utility/multi-kill/weapon kills, etc.) —
    leader cards on the left (best agent per stat), the full
    sortable/filterable table with a column picker and a total/average
    toggle on the right. Same layout/Alpine model as
    tournament/stats.blade.php.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('player.title.stats', ["player" => $player['handle']]))

@section('content')
    @include('public.player.header')

    <div
        x-data="statsTable({{ json_encode($stats) }}, {{ json_encode($weapons) }}, 'player')"
        class="max-w-[1600px] mx-auto space-y-4"
    >
        <div class="border-b border-border-subtle pb-2 mb-6">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-widest">
                {{ __("player.stats.title", ["player" => $player['handle']]) }}
            </h2>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-bg-card p-4 rounded border border-gray-800 shadow-xl mb-6">
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase font-black text-gray-500 tracking-wider">{{ __("player.stats.period") }}</span>
                <div class="flex bg-black/40 p-1 rounded-md border border-gray-800">
                    <a href="{{ request()->fullUrlWithQuery(['days' => 0, 'start_date' => null, 'end_date' => null]) }}"
                       class="px-3 py-1 text-[11px] font-bold rounded transition-all {{ (request('days', 0) == 0 && !request('start_date')) ? 'bg-gc-yellow text-white shadow-lg' : 'text-gray-400 hover:text-gray-200' }}">
                        {{ __('player.stats.all_time') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['days' => 30, 'start_date' => null, 'end_date' => null]) }}"
                       class="px-3 py-1 text-[11px] font-bold rounded transition-all {{ (request('days') == 30 && !request('start_date')) ? 'bg-gc-yellow text-white shadow-lg' : 'text-gray-400 hover:text-gray-200' }}">
                        {{ __('player.stats.last_30_days') }}
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['days' => 60, 'start_date' => null, 'end_date' => null]) }}"
                       class="px-3 py-1 text-[11px] font-bold rounded transition-all {{ (request('days') == 60) ? 'bg-gc-yellow text-white shadow-lg' : 'text-gray-400 hover:text-gray-200' }}">
                        {{ __('player.stats.last_60_days') }}
                    </a>
                </div>
            </div>

            <form id="player-stats-filter-form" action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center gap-2" aria-label="{{ __('player.stats.date_filter') }}">
                @foreach(request()->except(['start_date', 'end_date', 'agents', 'roles', 'maps']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                @include('public.partials.stats-filter-picker', ['formId' => 'player-stats-filter-form', 'name' => 'agents', 'label' => __('match.stats.filter_agent'), 'options' => $filterOptions['agents'], 'selected' => $selectedAgents])
                @include('public.partials.stats-filter-picker', ['formId' => 'player-stats-filter-form', 'name' => 'roles', 'label' => __('match.stats.filter_role'), 'options' => $filterOptions['roles'], 'selected' => $selectedRoles])
                @include('public.partials.stats-filter-picker', ['formId' => 'player-stats-filter-form', 'name' => 'maps', 'label' => __('match.stats.filter_map'), 'options' => $filterOptions['maps'], 'selected' => $selectedMaps])

                <div class="flex items-center bg-black/40 rounded-md border border-gray-800 overflow-hidden select-none">
                    <label for="start_date" class="sr-only">{{ __('player.stats.start_date') }}</label>
                    <input type="date"
                           id="start_date"
                           name="start_date"
                           value="{{ request('start_date') }}"
                           class="bg-transparent border-none text-[11px] text-gray-300 w-28 py-1 px-2 focus:outline-none [color-scheme:dark]">

                    <span class="text-gray-700 font-bold px-1 pointer-events-none whitespace-nowrap" aria-hidden="true">–</span>

                    <label for="end_date" class="sr-only">{{ __('player.stats.end_date') }}</label>
                    <input type="date"
                           id="end_date"
                           name="end_date"
                           value="{{ request('end_date') }}"
                           class="bg-transparent border-none text-[11px] text-gray-300 w-28 py-1 px-2 focus:outline-none [color-scheme:dark]">
                </div>
                <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-gray-300 p-1.5 rounded transition-colors ml-2" aria-label="{{ __('player.stats.filter_submit') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </form>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-3 space-y-6">
                @include('public.partials.stats-insights', ['insights' => $insights, 'namespace' => 'player.stats'])
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
                                    <th scope="col" class="p-3 w-12 text-center bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/50 shadow-[1px_0_0_0_#2a2a2a] md:shadow-none">{{ __("match.stats.agent_name") }}</th>

                                    <template x-for="col in allCols" :key="col.key">
                                        <th x-show="visibleCols.includes(col.key)" @click="sortBy(col.key)" scope="col" class="p-3 text-center border-b border-gray-800/50 hover:text-white transition-colors group cursor-pointer">
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
                                <template x-for="stat in filteredStats" :key="stat.agent_name">
                                    <tr class="group transition-colors hover:bg-white/[0.03]">
                                        <td class="p-2 text-center bg-[#0c0c0c] md:bg-transparent border-b border-gray-800/30 group-hover:bg-bg-card-hover transition-colors shadow-[1px_0_0_0_#2a2a2a] md:shadow-none">
                                            <img :src="'/storage/agents/' + agentSlug(stat.agent_name) + '.webp'" :alt="stat.agent_name" class="w-8 h-8 rounded border border-gray-700 bg-bg-main mx-auto" loading="lazy">
                                        </td>

                                        <template x-for="col in allCols" :key="col.key">
                                            <td x-show="visibleCols.includes(col.key)" class="p-2 text-center border-b border-gray-800/30"
                                                :class="col.key === 'kast' ? (val(stat, 'kast') >= 75 ? 'text-green-500' : 'text-gray-500') : 'text-gray-300'"
                                                :title="abilityTitle(stat, col.key)"
                                                x-text="formatVal(stat, col.key)">
                                            </td>
                                        </template>
                                    </tr>
                                </template>

                                <tr x-show="filteredStats.length === 0">
                                    <td colspan="20" class="p-8 text-center text-gray-600 uppercase font-black tracking-widest">{{ __("player.stats.no_data") }}</td>
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

            .no-scrollbar {
                -webkit-user-select: none; /* Safari */
                -ms-user-select: none; /* IE 10+ */
                user-select: none; /* Standard */
            }
        </style>
    </div>

    @include('public.partials.stats-table-script')
@endsection
