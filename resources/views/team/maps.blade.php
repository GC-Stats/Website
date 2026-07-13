{{--
    GC-Stats — Team maps page

    Lists every map played by the team with W-L record, ATK/DEF round
    winrates, and the agent compositions the team has played on each map.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('team.title.maps', ["team" => $team['name']]))

@section('content')
    @include('team.header')

    <div class="max-w-6xl mx-auto space-y-4">
        <section class="col-span-12 lg:col-span-6 space-y-4">
            @forelse($maps as $map)
                <div class="bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden">
                    <div class="relative p-4 md:p-5 flex flex-wrap items-center justify-between gap-4 border-b border-white/5 bg-cover bg-center"
                         style="background-image: linear-gradient(to right, rgba(0,0,0,0.75), rgba(0,0,0,0.4)), url('/storage/maps/{{ strtolower($map['map_name']) }}.webp')">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg md:text-xl font-black text-white uppercase tracking-tight drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)]">{{ $map['map_name'] }}</h3>
                            <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-md text-gray-200 bg-black/40 backdrop-blur-sm">
                                {{ __('team.maps.record') }}: {{ $map['wins'] }}-{{ $map['losses'] }}
                            </span>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="text-center">
                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-300 mb-1">{{ __('team.maps.atk_wr') }}</p>
                                <p class="text-sm font-black drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] {{ $map['atk_win_pct'] !== null && $map['atk_win_pct'] >= 50 ? 'text-green-500' : 'text-white' }}">
                                    {{ $map['atk_win_pct'] !== null ? $map['atk_win_pct'].'%' : '—' }}
                                </p>
                            </div>

                            <div class="text-center">
                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-300 mb-1">{{ __('team.maps.def_wr') }}</p>
                                <p class="text-sm font-black drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] {{ $map['def_win_pct'] !== null && $map['def_win_pct'] >= 50 ? 'text-green-500' : 'text-white' }}">
                                    {{ $map['def_win_pct'] !== null ? $map['def_win_pct'].'%' : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if(count($map['pick_rates']))
                        <div class="p-4 md:p-5 border-b border-white/5">
                            <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-2">{{ __('team.maps.pick_rate') }}</p>

                            <div class="flex flex-wrap gap-3">
                                @foreach($map['pick_rates'] as $rate)
                                    @php $glowColor = \App\Helpers\AgentRoles::shadowColorFor($rate['agent']); @endphp
                                    <div class="relative overflow-hidden bg-white/[0.02] rounded-lg pl-1.5 pr-2.5 py-1.5">
                                        @if($glowColor)
                                            <span class="absolute inset-0 blur-md" style="background: {{ $glowColor }};" aria-hidden="true"></span>
                                        @endif

                                        <div class="relative z-10 flex items-center gap-2">
                                            <x-agent-icon :agent="$rate['agent']" />
                                            <span class="text-[10px] font-black text-gray-300">{{ $rate['pick_pct'] }}%</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(count($map['comps']))
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = !open"
                                    class="relative z-10 w-full flex items-center justify-between gap-2 p-4 md:p-5 text-left transition-colors hover:bg-white/[0.03]">
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-500">
                                    {{ __('team.maps.comps') }} ({{ count($map['comps']) }})
                                </span>

                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition class="px-4 md:px-5 pt-3 pb-4 md:pb-5 space-y-2 border-t border-white/5">
                                @foreach($map['comps'] as $comp)
                                    <div x-data="{ open: false }" class="bg-white/[0.02] rounded-lg overflow-hidden">
                                        <button type="button" @click="open = !open" class="w-full flex flex-wrap items-center justify-between gap-3 px-3 py-2 text-left hover:bg-white/[0.03] transition-colors">
                                            <div class="inline-flex -space-x-2">
                                                @foreach($comp['agents'] as $agent)
                                                    <x-agent-icon :agent="$agent" />
                                                @endforeach
                                            </div>

                                            <div class="flex items-center gap-3 text-[10px] font-bold shrink-0">
                                                <span class="text-gray-500">{{ $comp['count'] }}x</span>
                                                <span class="{{ $comp['win_pct'] !== null && $comp['win_pct'] >= 50 ? 'text-green-500' : 'text-gray-500' }}">
                                                    {{ $comp['win_pct'] !== null ? $comp['win_pct'].'%' : '—' }}
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 text-gray-600 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </button>

                                        <div x-show="open" x-cloak x-transition class="px-3 pb-2 space-y-1 border-t border-white/5 pt-2">
                                            @foreach($comp['matches'] as $compMatch)
                                                <a href="{{ route('match.show', $compMatch['match_id']) }}"
                                                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-md text-[10px] font-bold text-gray-400 hover:bg-white/[0.03] hover:text-white transition-colors">
                                                    <span class="flex items-center gap-2 min-w-0">
                                                        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $compMatch['won'] ? 'bg-green-500' : 'bg-red-500/70' }}"></span>
                                                        <span class="truncate">{{ __('team.maps.vs') }} {{ $compMatch['opponent'] ?? '—' }}</span>
                                                    </span>

                                                    <span class="flex items-center gap-2 shrink-0">
                                                        <span class="text-gray-600">{{ \App\Helpers\PivotDate::isUnknown($compMatch['scheduled_at'] ?? null) ? __('match.unknown_date') : \Carbon\Carbon::parse($compMatch['scheduled_at'])->format('d M Y') }}</span>
                                                        <span class="text-gray-300">{{ $compMatch['own_score'] }}–{{ $compMatch['opp_score'] }}</span>
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <h3 class="text-center text-gray-400">{{ __('team.empty.maps') }}</h3>
            @endforelse
        </section>
    </div>
@endsection
