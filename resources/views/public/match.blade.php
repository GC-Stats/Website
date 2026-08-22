{{--
    GC-Stats — Match detail page

    Displays a single match: tournament header, teams, score, map vetoes,
    per-map results and player statistics.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@php
    $teamAName = $match['team_a_data']['name'] ?? ($match['status'] == 'finished' ? __('match.team_bye') : __('match.team_tbd'));
    $teamBName = $match['team_b_data']['name'] ?? ($match['status'] == 'finished' ? __('match.team_bye') : __('match.team_tbd'));
@endphp

@section('title', __("match.title", ["teamA" => $teamAName, "teamB" => $teamBName]))
@section('description', __("match.title", ["teamA" => $teamAName, "teamB" => $teamBName]) . ' — ' . ($match['tournament']['name'] ?? $match['tournament_name'] ?? config('app.name')))
@section('canonical', route('match.show', $match['id']))
@section('og_image', $match['tournament']['logo'] ?? asset('web-app-manifest-512x512.png'))

@push('schema')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'SportsEvent',
    'name' => __("match.title", ["teamA" => $teamAName, "teamB" => $teamBName]),
    'startDate' => $match['scheduled_at'] ?? $match['date'] ?? null,
    'eventStatus' => 'https://schema.org/EventScheduled',
    'url' => route('match.show', $match['id']),
    'competitor' => [
        ['@type' => 'SportsTeam', 'name' => $teamAName],
        ['@type' => 'SportsTeam', 'name' => $teamBName],
    ],
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
    <section class="mx-auto w-full max-w-[1600px] py-8 px-4">
        @if($inactive_access ?? false)
            <div class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 text-xs text-gc-yellow">
                {{ __('tournament.inactive_access') }}
            </div>
        @endif

        @can('matches.view')
            <div class="mb-3">
                <a href="{{ route('admin.matches.edit', [$match['tournament_id'], $match['id']]) }}"
                   class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition">
                    @svg('fas-user-shield', 'w-2.5 h-2.5', ['aria-hidden' => 'true'])
                    {{ __('layout.account.admin') }}
                </a>
            </div>
        @endcan

        <div class="flex flex-col lg:flex-row items-start gap-6">
        @if($encounters)
            <div class="w-full lg:w-[360px] shrink-0 order-2 lg:order-1">
                @include('public.partials.encounters', ['encounters' => $encounters])
            </div>
        @endif

        <div class="flex-1 min-w-0 w-full order-1 lg:order-2">
        <x-match.score-header :match="$match">
            @if ($match['status'] === 'finished')
                @include('components.public.match.vods', ['match' => $match, 'canLinkVods' => $canLinkVods ?? false])
            @else
                @include('components.public.match.streams', ['match' => $match, 'canLinkStreams' => $canLinkStreams ?? false])
            @endif

            @include('components.public.match.player-povs', ['match' => $match])

            <div class="mt-10 -mx-4 md:-mx-6">
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
        </x-match.score-header>
        </div>

        @if($headToHead)
            <div class="w-full lg:w-[360px] shrink-0 order-3">
                <div x-data="{
                    open: false,
                    copied: false,
                    copyLink() {
                        navigator.clipboard.writeText('{{ route('widget.head-to-head', array_filter(['team_a' => $match['team_a_id'], 'team_b' => $match['team_b_id'], 'patch' => $match['patch'] ?? null])) }}');
                        this.copied = true;
                        setTimeout(() => { this.copied = false; this.open = false; }, 1200);
                    }
                }">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <span class="text-[9px] font-black text-gray-600 uppercase tracking-[0.3em]">{{ __('head_to_head.title') }}</span>

                        <div class="relative" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-[9px] font-black uppercase tracking-widest text-gray-500 hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="18" cy="5" r="3"></circle>
                                    <circle cx="6" cy="12" r="3"></circle>
                                    <circle cx="18" cy="19" r="3"></circle>
                                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                                </svg>
                                {{ __('head_to_head.widget.share') }}
                            </button>

                            <div x-show="open" x-cloak x-transition.origin.top.right
                                 class="absolute right-0 z-20 mt-2 w-48 bg-[#0d0d0d] border border-white/10 rounded-xl shadow-2xl overflow-hidden">
                                <button type="button" @click="copyLink()" class="w-full text-left px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
                                    <span x-show="!copied">{{ __('head_to_head.widget.copy_link') }}</span>
                                    <span x-show="copied" x-cloak class="text-[var(--brand-yellow)]">{{ __('head_to_head.widget.link_copied') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <x-public.head-to-head :data="$headToHead" />
                </div>
            </div>
        @endif
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
                    <div class="space-y-12 animate-fadeIn">
                        @include('public.partials.team-stats-table', [
                            'statsA' => $totalA,
                            'statsB' => $totalB,
                            'teamAName' => $teamAName,
                            'teamBName' => $teamBName,
                            'teamALogo' => $match['team_a_data']['logo'] ?? null,
                            'teamBLogo' => $match['team_b_data']['logo'] ?? null,
                            'multiple' => true,
                        ])

                        @if(!empty($totalPerformance) || $totalEcoSummary['team_a']['eco']['total'] > 0 || $totalEcoSummary['team_b']['eco']['total'] > 0)
                            @include('public.partials.performance-economy', [
                                'teamAName' => $teamAName,
                                'teamBName' => $teamBName,
                                'teamALogo' => $match['team_a_data']['logo'] ?? null,
                                'teamBLogo' => $match['team_b_data']['logo'] ?? null,
                                'statsA' => $totalA,
                                'statsB' => $totalB,
                                'performance' => $totalPerformance,
                                'ecoSummary' => $totalEcoSummary,
                            ])
                        @endif
                    </div>
                </template>

                @if($match['status'] != "upcoming")
                    @foreach($match['game_maps'] as $map)
                        <div x-show="activeMap === {{ $map['id'] }}" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-12">

                            @if(!empty($map['note']))
                                <p class="text-center text-sm text-gray-400 italic">{{ $map['note'] }}</p>
                            @endif

                            @if(!empty($map["rounds"]))
                                @include('public.partials.round-history', ['map' => $map])
                            @endif

                            @php
                                $mStatsA = $map['stats_a'];
                                $mStatsB = $map['stats_b'];
                            @endphp

                            @include('public.partials.team-stats-table', [
                                'multiple' => false,
                                'statsA' => $mStatsA,
                                'statsB' => $mStatsB,
                                'teamAName' => $teamAName,
                                'teamBName' => $teamBName,
                                'teamALogo' => $match['team_a_data']['logo'] ?? null,
                                'teamBLogo' => $match['team_b_data']['logo'] ?? null,
                            ])

                            @if(!empty($map['performance']) || $map['eco_summary']['team_a']['eco']['total'] > 0 || $map['eco_summary']['team_b']['eco']['total'] > 0)
                                @include('public.partials.performance-economy', [
                                    'teamAName' => $teamAName,
                                    'teamBName' => $teamBName,
                                    'teamALogo' => $match['team_a_data']['logo'] ?? null,
                                    'teamBLogo' => $match['team_b_data']['logo'] ?? null,
                                    'statsA' => $mStatsA,
                                    'statsB' => $mStatsB,
                                    'performance' => $map['performance'],
                                    'ecoSummary' => $map['eco_summary'],
                                ])
                            @endif
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

        <div class="mt-12">
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 block mb-4">{{ __('forum.category.match') }}</span>
            <livewire:forum-thread lazy :subject-type="\App\Models\Matchs::class" :subject-id="$match['id']" />
        </div>
    </section>

    @if($headToHead)
        @vite('resources/js/public/head-to-head/index.js')
    @endif
@endsection
