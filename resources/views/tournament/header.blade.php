{{--
    GC-Stats — Tournament header partial

    Renders the tournament profile header (logo, name, region, category,
    dates, prize pool) shared across all tournament sub-pages.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="block group mb-6">
    <div class="relative bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 group-hover:bg-white/[0.04] group-hover:shadow-[0_20px_40px_rgba(0,0,0,0.6)]">
        <div class="p-4 md:p-6 flex flex-col gap-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 w-full">

                <div class="flex items-center md:items-center gap-4 md:gap-5 w-full flex-1 min-w-0">
                    <a href="{{ isset($previewCode) ? route('tournament.preview', $previewCode) : route('tournaments.show', [$tournament['id'], str($tournament['name'] ?? '')->slug()]) }}" class="flex items-center gap-4 md:gap-5 flex-1 min-w-0">
                        <div class="relative flex-shrink-0">
                            <img src="{{ $tournament['logo'] }}" alt="{{ $tournament['name'] }}"
                                 class="w-16 h-16 md:w-32 md:h-32 object-contain border border-white/10 rounded-lg bg-black/40 p-2">
                        </div>

                        <div class="flex flex-col justify-center min-w-0">
                            <div class="flex items-center gap-2 text-[8px] md:text-[10px] font-bold tracking-widest text-gray-500 uppercase mb-1">
                                <x-far-calendar class="text-[var(--brand-yellow)]/50 w-3.5 w-3.5" aria-hidden="true" />
                                <span class="whitespace-nowrap">{{ \Carbon\Carbon::parse($tournament['start_date'])->format('d M') }}</span>
                                <span class="w-2 h-[1px] bg-gray-700"></span>
                                <span class="whitespace-nowrap">{{ \Carbon\Carbon::parse($tournament['end_date'])->format('d M Y') }}</span>
                            </div>

                            <h1 class="text-xl md:text-4xl font-black text-white uppercase tracking-tight leading-none mb-2 truncate">
                                {{ $tournament['name'] }}
                            </h1>

                            @if($tournament['description'])
                                <p class="text-[9px] md:text-[10px] font-bold uppercase text-gray-500 italic leading-tight line-clamp-1 md:line-clamp-none">
                                    {{ $tournament['description'] }}
                                </p>
                            @endif
                        </div>
                    </a>
                </div>

                <div class="flex flex-wrap md:flex-col gap-2 w-full md:w-auto md:min-w-[180px] md:pl-6 md:border-l md:border-white/5">
                    @if($tournament['liquipedia_link'] ?? null)
                        <a href="{{ $tournament['liquipedia_link'] }}" target="_blank" rel="noopener noreferrer"
                           aria-label="Liquipedia: {{ $tournament['name'] }}"
                           class="inline-flex items-center px-3 py-1.5 text-[8px] md:text-[9px] font-black uppercase tracking-widest rounded-md text-gray-400 bg-white/5 hover:text-[var(--brand-yellow)] transition-colors">
                            <img src="https://liquipedia.net/commons/extensions/TeamLiquidIntegration/resources/pagelogo/liquipedia_icon_menu.png"
                                 alt="" class="w-3 h-3 inline-block mr-1.5 object-contain">
                            Liquipedia
                        </a>
                    @endif

                    <span class="px-3 py-1.5 text-[8px] md:text-[9px] font-black uppercase tracking-widest rounded-md
                        @if($tournament['status'] == 'live') text-[var(--brand-yellow)] bg-[var(--brand-yellow)]/10
                        @elseif($tournament['status'] == 'upcoming') text-green-500 bg-green-500/10
                        @elseif($tournament['status'] == 'finished') text-gray-500 bg-gray-500/10
                        @endif">
                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 {{ $tournament['status'] == 'live' ? 'animate-pulse bg-current' : 'bg-current/50' }}"></span>
                        {{ $tournament['status'] }}
                    </span>

                    @if($tournament['region'])
                        @php $color = config('regions.colors.' . $tournament['region'], '#666666'); @endphp

                        <span class="px-3 py-1.5 text-[8px] md:text-[9px] font-black uppercase tracking-widest rounded-md"
                          style="color: {{ $color }}; background: {{ $color }}15;">
                            <x-fas-globe-europe class="w-3 h-3 inline-block mr-1.5" style="color: {{ $color }}70" aria-hidden="true" /> {{ $tournament['region'] }}
                        </span>
                    @endif

                    @if($tournament['location'])
                        <span class="px-3 py-1.5 text-[8px] md:text-[9px] font-black uppercase tracking-widest rounded-md text-gray-400 bg-white/5">
                            <x-fas-location-dot class="w-3 h-3 inline-block mr-1.5 text-[var(--brand-yellow)]/70" aria-hidden="true" /> {{ $tournament['location'] }}
                        </span>
                    @endif

                    @if($tournament['prize_pool'])
                        <span class="px-3 py-1.5 text-[8px] md:text-[9px] font-black uppercase tracking-widest rounded-md text-[var(--brand-yellow)] bg-[var(--brand-yellow)]/10 border border-[var(--brand-yellow)]/20">
                            <x-fas-money-bills class="w-3 h-3 inline-block mr-1.5" aria-hidden="true" /> {{ $tournament['prize_pool'] }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <nav aria-label="{{ __('tournament.nav.aria_label') }}" class="bg-black/20 border-t border-white/5 overflow-x-auto no-scrollbar">
            <div class="flex justify-start md:justify-center min-w-max md:min-w-0">
                @php
                    $isPreview = isset($previewCode);
                    $tournamentParam = [$tournament['id'] ?? null, Str::routeSlug($tournament['name'] ?? '', $tournament['id'] ?? null)];
                    $navItems = [
                        ['route' => $isPreview ? 'tournament.preview' : 'tournaments.show', 'param' => $isPreview ? $previewCode : $tournamentParam, 'label' => __('tournament.nav.overview')],
                        ['route' => $isPreview ? 'tournament.preview.matches' : 'tournaments.matches', 'param' => $isPreview ? $previewCode : $tournamentParam, 'label' => __('tournament.nav.matches')],
                    ];
                    if (! $isPreview) {
                        $navItems[] = ['route' => 'tournaments.stats', 'param' => $tournamentParam, 'label' => __('tournament.nav.stats')];
                        $navItems[] = ['route' => 'tournaments.maps', 'param' => $tournamentParam, 'label' => __('tournament.nav.maps')];
                    }
                @endphp

                @foreach($navItems as $item)
                    @php $isActive = request()->routeIs($item['route']); @endphp
                    <a href="{{ Route::has($item['route']) ? route($item['route'], $item['param']) : '#' }}"
                       @if($isActive) aria-current="page" @endif
                       class="relative px-6 md:px-10 py-4 text-[10px] md:text-[11px] font-black uppercase tracking-[0.2em] transition-all group/navbar whitespace-nowrap {{ $isActive ? 'text-[var(--brand-yellow)]' : 'text-gray-500 hover:text-white' }}">

                        {{ $item['label'] }}

                        <span class="absolute bottom-0 left-0 h-0.5 bg-[var(--brand-yellow)] transition-all duration-300 ease-in-out
                            {{ $isActive ? 'w-full' : 'w-0 group-hover/navbar:w-full' }}">
                        </span>
                    </a>
                @endforeach
            </div>
        </nav>
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
