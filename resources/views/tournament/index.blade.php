{{--
    GC-Stats — Tournament listing page

    Lists active tournaments with filters (region, category, year) and
    sorting.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('index.title'))

@section('content')
    <div class="space-y-8 mb-12">
        <div class="flex flex-wrap items-center gap-6 p-1">

            <div class="relative" x-data="{ open: false }">
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-600 mb-2 block ml-1" id="region-filter-label">{{ __('tournament.index.filter.region.title') }}</span>
                <button @click="open = !open"
                        aria-expanded="false"
                        :aria-expanded="open.toString()"
                        aria-haspopup="listbox"
                        aria-labelledby="region-filter-label"
                        class="min-w-[160px] flex items-center justify-between bg-white/[0.03] border border-white/10 px-4 py-3 rounded-xl hover:bg-white/[0.05] hover:border-[var(--brand-yellow)]/30 transition-all group">
                <span class="text-[10px] font-black uppercase tracking-widest {{ request('region') ? 'text-[var(--brand-yellow)]' : 'text-gray-400' }}">
                    {{ request('region') ?? __('tournament.index.filter.region.default') }}
                </span>
                    <x-fas-chevron-down class="w-3 h-3 inline-block transition-transform" x-bind:class="open ? 'rotate-180' : ''" aria-hidden="true" />
                </button>

                <div x-show="open" @click.outside="open = false" x-transition role="listbox" aria-labelledby="region-filter-label" class="absolute z-50 mt-2 w-full bg-[#121212] border border-white/10 rounded-xl shadow-2xl overflow-hidden backdrop-blur-md">
                    <a href="{{ request()->fullUrlWithQuery(['region' => '']) }}" class="block px-4 py-3 text-[10px] font-bold text-gray-500 hover:bg-white/5 hover:text-white uppercase transition-colors">Tout</a>
                    @foreach($regions as $region)
                        <a href="{{ request()->fullUrlWithQuery(['region' => $region]) }}" class="block px-4 py-3 text-[10px] font-bold {{ request('region') == $region ? 'text-[var(--brand-yellow)] bg-white/5' : 'text-gray-400' }} hover:bg-white/5 hover:text-white uppercase transition-colors">
                            {{ $region }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="relative" x-data="{ open: false }">
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-600 mb-2 block ml-1" id="category-filter-label">{{ __('tournament.index.filter.category.title') }}</span>
                <button @click="open = !open"
                        aria-expanded="false"
                        :aria-expanded="open.toString()"
                        aria-haspopup="listbox"
                        aria-labelledby="category-filter-label"
                        class="min-w-[160px] flex items-center justify-between bg-white/[0.03] border border-white/10 px-4 py-3 rounded-xl hover:bg-white/[0.05] hover:border-[var(--brand-yellow)]/30 transition-all">
                <span class="text-[10px] font-black uppercase tracking-widest {{ request('category') ? 'text-[var(--brand-yellow)]' : 'text-gray-400' }}">
                    {{ request('category') ?? __('tournament.index.filter.category.default') }}
                </span>
                    <x-fas-chevron-down class="w-3 h-3 inline-block" aria-hidden="true" />
                </button>

                <div x-show="open" @click.outside="open = false" x-transition role="listbox" aria-labelledby="category-filter-label" class="absolute z-50 mt-2 w-full bg-[#121212] border border-white/10 rounded-xl shadow-2xl overflow-hidden backdrop-blur-md">
                    <a href="{{ request()->fullUrlWithQuery(['category' => '']) }}" class="block px-4 py-3 text-[10px] font-bold text-gray-500 hover:bg-white/5 uppercase transition-colors">Tout</a>
                    @foreach($categories as $cat)
                        <a href="{{ request()->fullUrlWithQuery(['category' => $cat]) }}" class="block px-4 py-3 text-[10px] font-bold {{ request('category') == $cat ? 'text-[var(--brand-yellow)] bg-white/5' : 'text-gray-400' }} hover:bg-white/5 uppercase transition-colors">
                            {{ $cat }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="relative" x-data="{ open: false }">
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-600 mb-2 block ml-1" id="year-filter-label">{{ __('tournament.index.filter.period.title') }}</span>
                <button @click="open = !open"
                        aria-expanded="false"
                        :aria-expanded="open.toString()"
                        aria-haspopup="listbox"
                        aria-labelledby="year-filter-label"
                        class="min-w-[120px] flex items-center justify-between bg-white/[0.03] border border-white/10 px-4 py-3 rounded-xl hover:bg-white/[0.05] hover:border-[var(--brand-yellow)]/30 transition-all">
                <span class="text-[10px] font-black uppercase tracking-widest {{ request('year') ? 'text-[var(--brand-yellow)]' : 'text-gray-400' }}">
                    {{ request('year') ?? __('tournament.index.filter.period.default') }}
                </span>
                    <x-fas-calendar-day class="w-3 h-3 inline-block opacity-40" aria-hidden="true" />
                </button>

                <div x-show="open" @click.outside="open = false" x-transition role="listbox" aria-labelledby="year-filter-label" class="absolute z-50 mt-2 w-full bg-[#121212] border border-white/10 rounded-xl shadow-2xl overflow-hidden backdrop-blur-md">
                    <a href="{{ request()->fullUrlWithQuery(['year' => '']) }}" class="block px-4 py-3 text-[10px] font-bold text-gray-500 hover:bg-white/5 uppercase transition-colors">Tout</a>
                    @foreach($years as $year)
                        <a href="{{ request()->fullUrlWithQuery(['year' => $year]) }}" class="block px-4 py-3 text-[10px] font-bold {{ request('year') == $year ? 'text-[var(--brand-yellow)] bg-white/5' : 'text-gray-400' }} hover:bg-white/5 uppercase transition-colors">
                            {{ $year }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="h-px flex-grow bg-gradient-to-r from-[var(--brand-yellow)]/20 via-white/5 to-transparent"></div>
            @php
                $getNextDirection = function($sortType) use ($currentSort, $currentDirection) {
                    if ($currentSort !== $sortType) {
                        return $sortType === 'name' ? 'asc' : 'desc';
                    }
                    return $currentDirection === 'asc' ? 'desc' : 'asc';
                };
            @endphp

            <div class="flex items-center gap-4 shrink-0">
                <span class="text-[8px] font-black uppercase tracking-[0.4em] text-gray-600">{{ __('tournament.index.sort.title') }}</span>
                <div class="flex bg-white/[0.02] border border-white/5 p-1 rounded-xl">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'date', 'direction' => $getNextDirection('date')]) }}"
                       class="group flex items-center gap-2 px-6 py-2 text-[9px] font-black uppercase rounded-lg transition-all {{ $currentSort == 'date' ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white' }}">
                        <span>Date</span>
                        @if($currentSort == 'date')
                            <x-icon :name="'fas-sort-amount-' . ($currentDirection === 'asc' ? 'up' : 'down')" class="w-3.5 h-3.5 inline-block" aria-hidden="true" />
                        @endif
                    </a>

                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => $getNextDirection('name')]) }}"
                       class="group flex items-center gap-2 px-6 py-2 text-[9px] font-black uppercase rounded-lg transition-all {{ $currentSort == 'name' ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white' }}">
                        <span>Nom</span>
                        @if($currentSort == 'name')
                            <x-icon :name="'fas-sort-alpha-' . ($currentDirection === 'asc' ? 'up' : 'down-alt')" class="w-3.5 h-3.5 inline-block" aria-hidden="true" />
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        @foreach($tournaments as $tournament)
            <div class="group relative bg-[#0d0d0d] border border-white/5 rounded-2xl overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-500">
                <div class="absolute inset-0 bg-linear-to-br from-[var(--brand-yellow)]/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>

                <div class="relative p-6 flex flex-col lg:flex-row gap-8 items-center">
                    <div class="flex items-center gap-6 w-full lg:w-1/3">
                        <div class="relative shrink-0">
                            <div class="relative inline-block">
                                <img src="{{ $tournament['logo'] }}"
                                     alt="{{ $tournament['name'] }}"
                                     class="w-24 h-24 md:w-28 md:h-28 object-contain bg-black/60 border border-white/10 rounded-xl p-4 transition-all duration-500 group-hover:bg-black/40 group-hover:border-[var(--brand-yellow)]/20">

                                @if($tournament['status'] === 'live')
                                    <div class="absolute top-2 left-2 flex items-center gap-1.5 bg-black/60 backdrop-blur-md" role="status">
                                        <span class="relative flex h-2 w-2" aria-hidden="true">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                        </span>
                                        <span class="text-[7px] font-black text-red-500 uppercase tracking-tighter">Live</span>
                                    </div>
                                @endif

                                <div class="absolute -top-1 -right-1 w-2 h-2 border-t border-r border-white/20"></div>
                                <div class="absolute -bottom-1 -left-1 w-2 h-2 border-b border-l border-white/20"></div>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] font-black text-[var(--brand-yellow)] uppercase tracking-[0.2em] mb-1 block">
                                {{ $tournament['category'] }}
                            </span>
                            <h2 class="text-xl md:text-2xl font-black text-white uppercase italic tracking-tight group-hover:translate-x-1 transition-transform">
                                {{ $tournament['name'] }}
                            </h2>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-[9px] font-bold text-gray-500 flex items-center gap-1.5">
                                    <x-fas-calendar class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> {{ \Carbon\Carbon::parse($tournament['start_date'])->format('d M') }} - {{ \Carbon\Carbon::parse($tournament['end_date'])->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-1 items-center justify-around w-full py-4 border-y lg:border-y-0 lg:border-x border-white/5 px-4">
                        <div class="text-center">
                            <p class="text-[8px] font-black uppercase text-gray-600 tracking-widest mb-1">{{ __("tournament.index.teams") }}</p>
                            <p class="text-lg font-black text-white font-mono">{{ $tournament['teams_count'] ?? '?' }}</p>
                        </div>

                        <div class="text-center">
                            <p class="text-[8px] font-black uppercase text-gray-600 tracking-widest mb-1">{{ __("tournament.index.region") }}</p>
                            <p class="text-lg font-black text-white font-mono">{{ $tournament['region'] }}</p>
                        </div>

                        @if($tournament['location'])
                            <div class="text-center">
                                <p class="text-[8px] font-black uppercase text-gray-600 tracking-widest mb-1">{{ __("tournament.index.location") }}</p>
                                <p class="text-lg font-black text-white font-mono">{{ $tournament['location'] }}</p>
                            </div>
                        @endif

                        @if($tournament['prize_pool'])
                            <div class="text-center">
                                <p class="text-[8px] font-black uppercase text-gray-600 tracking-widest mb-1">{{ __("tournament.index.prize_pool") }}</p>
                                <p class="text-lg font-black text-white font-mono">{{ $tournament['prize_pool'] }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col items-center lg:items-end gap-4 w-full lg:w-1/4">
                        <a href="{{ route('tournaments.show', [$tournament['id'], str($tournament['name'] ?? '')->slug()]) }}"
                           class="w-full lg:w-auto px-8 py-3 bg-white text-black text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-[var(--brand-yellow)] transition-colors text-center">
                            {{ __('tournament.index.see_tournament') }}
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>



@endsection

