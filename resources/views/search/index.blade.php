{{--
    GC-Stats — Search results page

    Lists all players, teams and tournaments matching a search query, with
    type filtering and sorting. Reached via the "see more" link in the
    header search dropdown.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('global-search.title'))

@section('content')
    <div class="space-y-8 mb-12">
        <div>
            <h1 class="text-2xl md:text-3xl font-black uppercase italic tracking-tight text-white">
                @if(mb_strlen($query) >= 2)
                    {{ __('global-search.results_for', ['query' => $query]) }}
                @else
                    {{ __('global-search.title') }}
                @endif
            </h1>
            @if(mb_strlen($query) >= 2)
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-2">
                    {{ trans_choice('global-search.count', $totalCount, ['count' => $totalCount]) }}
                </p>
            @endif
        </div>

        @if(mb_strlen($query) >= 2)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex bg-white/[0.02] border border-white/5 p-1 rounded-xl">
                    @foreach(['all' => __('global-search.filter.all'), 'players' => __('layout.type.players'), 'teams' => __('layout.type.teams'), 'tournaments' => __('layout.type.tournaments')] as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['type' => $key, 'page' => null]) }}"
                           class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all {{ $type == $key ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-[8px] font-black uppercase tracking-[0.4em] text-gray-600">{{ __('global-search.sort.title') }}</span>
                    <div class="flex bg-white/[0.02] border border-white/5 p-1 rounded-xl">
                        @foreach(['relevance' => __('global-search.sort.relevance'), 'name' => __('global-search.sort.name'), 'popularity' => __('global-search.sort.popularity')] as $key => $label)
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => null]) }}"
                               class="px-4 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all {{ $sort == $key ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-500 hover:text-white' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if(mb_strlen($query) < 2)
        <div class="px-4 py-16 text-center">
            <x-fas-search class="w-8 h-8 inline-block text-gray-700 mb-4" aria-hidden="true" />
            <p class="text-xs font-black uppercase tracking-widest text-gray-500">{{ __('global-search.empty_query') }}</p>
        </div>
    @elseif($results->count() === 0)
        <div class="px-4 py-16 text-center">
            <x-fas-search class="w-8 h-8 inline-block text-gray-700 mb-4" aria-hidden="true" />
            <p class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">{{ __('global-search.no_results', ['query' => $query]) }}</p>
            <p class="text-[10px] font-bold text-gray-600">{{ __('global-search.no_results_hint') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($results as $item)
                @php $displayName = $item['handle'] ?? $item['name']; @endphp
                <a href="{{ route($item['type'] . '.show', [$item['id'], str($displayName ?? '')->slug()]) }}"
                   class="group flex items-center gap-4 bg-[#0d0d0d] border border-white/5 rounded-2xl p-4 hover:border-[var(--brand-yellow)]/30 transition-all duration-300">
                    <div class="relative shrink-0 w-14 h-14 flex items-center justify-center bg-black/60 border border-white/10 rounded-xl overflow-hidden">
                        @if($item['type'] === 'tournaments')
                            <img src="{{ $item['logo'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-contain p-2 opacity-80 group-hover:opacity-100 transition-opacity">
                        @elseif($item['type'] === 'teams')
                            <img src="{{ $item['logo'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-contain p-2 logo-filter">
                        @else
                            <img src="{{ $item['photo'] }}" alt="{{ $item['handle'] }}" class="w-full h-full object-cover">
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--brand-yellow)]/80 block mb-1">
                            // {{ __('layout.type.' . $item['type']) }}
                        </span>
                        <p class="text-sm font-black uppercase tracking-tight text-white truncate group-hover:text-[var(--brand-yellow)] transition-colors">
                            {{ $displayName }}
                        </p>
                        @if(isset($item['country_code']))
                            <span class="fi fi-{{ strtolower($item['country_code'] ?? 'un') }} fis rounded-[2px] mt-1.5 inline-block shadow-sm"
                                  aria-label="{{ $item['country_code'] ?? '' }}"></span>
                        @endif
                    </div>

                    <x-fas-chevron-right class="w-3 h-3 inline-block text-[var(--brand-yellow)] opacity-0 group-hover:opacity-100 transition-all transform -translate-x-2 group-hover:translate-x-0 shrink-0" aria-hidden="true" />
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $results->links() }}
        </div>
    @endif
@endsection
