{{--
    GC-Stats — Data Explorer query builder

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('data_explorer.builder.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-10 lg:col-start-2 space-y-6"
                  x-data="dataExplorerBuilder({
                    executeUrl: '{{ route('data-explorer.builder.execute') }}',
                    schema: {{ \Illuminate\Support\Js::from($schema) }},
                    operators: {{ \Illuminate\Support\Js::from($operators) }},
                  })">
            <div class="border-b border-border-subtle pb-6 flex items-center justify-between gap-4">
                <a href="{{ route('data-explorer.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-500 hover:text-white transition">
                    @svg('fas-arrow-left', 'w-3 h-3', ['aria-hidden' => 'true'])
                    {{ __('data_explorer.builder.back_to_query') }}
                </a>
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white text-center flex-1">{{ __('data_explorer.builder.title') }}</h1>
                <div class="w-[120px]"></div>
            </div>

            <div class="bg-white/5 border border-border-subtle rounded-sm px-4 py-3 flex gap-3">
                @svg('fas-circle-info', 'w-4 h-4 text-gc-yellow shrink-0 mt-0.5', ['aria-hidden' => 'true'])
                <div class="text-xs text-gray-400 leading-relaxed space-y-1">
                    <p class="text-gray-300 font-semibold">{{ __('data_explorer.builder.explainer_title') }}</p>
                    <p>{{ __('data_explorer.builder.explainer_body') }}</p>
                    <p class="text-gray-500">{{ __('data_explorer.builder.explainer_free') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Measures: one row per base field, with a dropdown of the
                     aggregations actually available for it (Cube has no ad-hoc
                     "pick any aggregation" — each combination is its own
                     pre-defined measure, e.g. matches.avg_team_a_score). --}}
                <div class="bg-bg-card border border-border-subtle rounded-sm p-5 space-y-3">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('data_explorer.builder.measures_title') }}</h2>
                    <p class="text-[11px] text-gray-500">{{ __('data_explorer.builder.measures_help') }}</p>
                    <input type="text" x-model="measureSearch" placeholder="{{ __('data_explorer.builder.search_placeholder') }}"
                           class="w-full bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    <div class="max-h-72 overflow-y-auto space-y-1 pr-1">
                        <template x-for="group in measureGroups" :key="group.key">
                            <div class="flex items-center gap-2 px-2 py-1.5 rounded-sm hover:bg-white/5">
                                <span class="flex-1 min-w-0 text-xs text-gray-300 truncate" x-text="group.key"></span>

                                <select x-model="pendingAgg[group.key]" :disabled="group.items.length <= 1"
                                        class="shrink-0 bg-[#050505] border border-border-subtle rounded-sm px-2 py-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow transition disabled:opacity-50">
                                    <template x-for="item in group.items" :key="item.full">
                                        <option :value="item.full" x-text="item.aggLabel"></option>
                                    </template>
                                </select>

                                <button type="button" @click="addMeasureFromGroup(group)"
                                        :disabled="selectedMeasures.includes(pendingAgg[group.key] || group.items[0]?.full)"
                                        class="shrink-0 px-2 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md transition bg-gc-yellow text-black hover:opacity-90 disabled:opacity-30 disabled:pointer-events-none">
                                    {{ __('data_explorer.builder.add_measure') }}
                                </button>
                            </div>
                        </template>
                        <p x-show="measureGroups.length === 0" class="text-xs text-gray-600 px-2 py-1.5">{{ __('data_explorer.builder.no_fields') }}</p>
                    </div>
                </div>

                {{-- Dimensions --}}
                <div class="bg-bg-card border border-border-subtle rounded-sm p-5 space-y-3">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('data_explorer.builder.dimensions_title') }}</h2>
                    <p class="text-[11px] text-gray-500">{{ __('data_explorer.builder.dimensions_help') }}</p>
                    <input type="text" x-model="dimensionSearch" placeholder="{{ __('data_explorer.builder.search_placeholder') }}"
                           class="w-full bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    <div class="max-h-72 overflow-y-auto space-y-1 pr-1">
                        <template x-for="field in filteredDimensions" :key="field">
                            <label class="flex items-center gap-2 text-xs text-gray-300 hover:text-white px-2 py-1.5 rounded-sm hover:bg-white/5 cursor-pointer">
                                <input type="checkbox" :value="field" :checked="selectedDimensions.includes(field)" @change="toggleDimension(field)"
                                       class="accent-gc-yellow">
                                <span x-text="field"></span>
                            </label>
                        </template>
                        <p x-show="filteredDimensions.length === 0" class="text-xs text-gray-600 px-2 py-1.5">{{ __('data_explorer.builder.no_fields') }}</p>
                    </div>
                </div>
            </div>

            {{-- Selected fields — color + icon distinguish measures (yellow, calculator)
                 from dimensions (blue, tag) since both end up in the same strip. --}}
            <div x-show="selectedMeasures.length > 0 || selectedDimensions.length > 0" class="flex flex-wrap items-center gap-2">
                <template x-for="item in selectedFieldsWithType" :key="item.field">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg border"
                          :class="item.type === 'measure' ? 'bg-gc-yellow/10 border-gc-yellow/30 text-gc-yellow' : 'bg-blue-500/10 border-blue-500/30 text-blue-400'">
                        <span x-show="item.type === 'measure'">@svg('fas-calculator', 'w-2.5 h-2.5', ['aria-hidden' => 'true'])</span>
                        <span x-show="item.type === 'dimension'">@svg('fas-tag', 'w-2.5 h-2.5', ['aria-hidden' => 'true'])</span>
                        <span x-text="item.field"></span>
                        <button type="button" @click="deselectField(item.field)" class="hover:text-white">&times;</button>
                    </span>
                </template>
            </div>
            <div x-show="selectedMeasures.length > 0 || selectedDimensions.length > 0" class="flex items-center gap-4 text-[10px] text-gray-500 uppercase tracking-widest">
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gc-yellow"></span> {{ __('data_explorer.builder.measures_title') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-400"></span> {{ __('data_explorer.builder.dimensions_title') }}</span>
            </div>

            {{-- Filters --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('data_explorer.builder.filters_title') }}</h2>
                    <button type="button" @click="addFilter()" :disabled="selectedFields.length === 0"
                            class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10 disabled:opacity-30 disabled:pointer-events-none">
                        {{ __('data_explorer.builder.add_filter') }}
                    </button>
                </div>

                <p x-show="selectedFields.length === 0" class="text-xs text-gray-600">{{ __('data_explorer.builder.select_fields_first') }}</p>
                <p x-show="selectedFields.length > 0 && filters.length === 0" class="text-xs text-gray-600">{{ __('data_explorer.builder.no_filters') }}</p>
                <p class="text-[11px] text-gray-500">{{ __('data_explorer.builder.scope_hint') }}</p>

                <template x-for="(filter, i) in filters" :key="i">
                    <div class="flex flex-wrap items-center gap-2">
                        <select x-model="filter.member" class="bg-[#050505] border border-border-subtle rounded-sm px-2 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                            <template x-for="field in selectedFields" :key="field">
                                <option :value="field" x-text="field"></option>
                            </template>
                        </select>
                        <select x-model="filter.operator" class="bg-[#050505] border border-border-subtle rounded-sm px-2 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                            <template x-for="op in operators" :key="op">
                                <option :value="op" x-text="op"></option>
                            </template>
                        </select>
                        <input type="text" x-model="filter.values" x-show="filter.operator !== 'set' && filter.operator !== 'notSet'"
                               placeholder="{{ __('data_explorer.builder.values_placeholder') }}"
                               class="flex-1 min-w-[140px] bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <button type="button" @click="removeFilter(i)" class="text-gray-500 hover:text-red-400 transition px-2">&times;</button>
                    </div>
                </template>
            </div>

            {{-- Limit + submit --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold uppercase tracking-widest text-gray-500 shrink-0">{{ __('data_explorer.builder.limit_label') }}</label>
                    <input type="number" x-model="limit" min="1" max="5000" placeholder="{{ __('data_explorer.builder.limit_placeholder') }}"
                           class="w-32 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                </div>

                <button type="button" @click="submit()" :disabled="loading || (selectedMeasures.length === 0 && selectedDimensions.length === 0)"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 disabled:opacity-50">
                    <span x-show="!loading">{{ __('data_explorer.builder.submit') }}</span>
                    <span x-show="loading" x-cloak>{{ __('data_explorer.index.loading') }}</span>
                </button>

                <div x-show="error" x-cloak class="bg-red-500/10 border border-red-500/30 rounded-sm px-4 py-3 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-red-400" x-text="error"></p>
                        <p class="text-[11px] text-red-400/60 mt-1" x-show="errorId" x-cloak>
                            {{ __('data_explorer.index.error_id') }}: <span class="font-mono" x-text="errorId"></span>
                        </p>
                    </div>
                    <button type="button" x-show="canRetry" @click="submit()"
                            class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                        {{ __('data_explorer.index.retry') }}
                    </button>
                </div>

                <x-data-explorer.result-panel>
                    <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-border-subtle text-gray-400">
                        <span x-text="resultRows.length"></span> {{ __('data_explorer.builder.rows_label') }}
                    </span>
                </x-data-explorer.result-panel>
            </div>
        </section>
    </div>
@endsection
