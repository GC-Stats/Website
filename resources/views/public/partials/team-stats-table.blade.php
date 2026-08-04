{{--
    GC-Stats — Face-to-face team stats table partial

    Ported from the "Match Stats" Claude Design mockup: a CSS-grid
    face-to-face row layout (team header with logo/name/comp, mirrored
    player rows either side of a center divider, MVP badge on the top ACS
    scorer(s)). Colors were swapped for this site's dark/brand-yellow
    palette.

    Agent and Player are two separate fixed-width columns (not flexible),
    so the player name always starts at the same x position regardless of
    how many agents are stacked in the Agent column (1 on a single map, up
    to a few on "All Maps"). The table still stretches to the card's full
    width via a flexible spacer column placed between the identity block
    (Agent + Player) and the stat block on each side — that's the natural
    "seam" for leftover space, not a byproduct of a name column stretching.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $gridCols = '100px 160px minmax(16px,1fr) 44px 82px 42px 42px 54px 38px 16px 38px 54px 42px 42px 82px 44px minmax(16px,1fr) 160px 100px';

    // Falls back to the single agent_name when the aggregated `agents` list
    // isn't present (e.g. a cached "All Maps" payload built before it existed).
    $rowAgents = function (?array $stat) use ($multiple) {
        if (! $stat) {
            return [];
        }

        if ($multiple) {
            return $stat['agents'] ?? array_filter([$stat['agent_name'] ?? null]);
        }

        return array_filter([$stat['agent_name'] ?? null]);
    };

    // Team comp shown in the header: each player's primary agent, same
    // agents-with-fallback logic as the row cells, capped implicitly by
    // however many players are in the roster (normally 5).
    $headerAgents = function (array $stats) use ($rowAgents) {
        return collect($stats)
            ->map(fn ($s) => ($s['agents'][0] ?? null) ?? ($s['agent_name'] ?? null))
            ->filter()
            ->values()
            ->all();
    };

    $headerAgentsA = $headerAgents($statsA);
    $headerAgentsB = $headerAgents($statsB);

    $maxAcs = collect($statsA)->merge($statsB)->max('acs');
    $rowCount = max(count($statsA), count($statsB));

    $fkColor = function ($fk, $fd) {
        if ($fk > $fd) {
            return 'text-green-400';
        }

        return 'text-gray-400';
    };
@endphp

<div class="bg-[#0d0d0d] rounded-2xl border border-white/5 shadow-2xl w-full overflow-hidden">
    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 px-5 py-4 bg-white/[0.02] border-b border-white/5">
        <div class="flex items-center gap-2.5 min-w-0">
            <img src="{{ $teamALogo ?? asset('storage/images/default-team.webp') }}"
                 alt="{{ $teamAName }}" loading="lazy"
                 class="w-8 h-8 rounded-lg object-contain bg-white/[0.03] shrink-0">
            <span class="font-black italic text-white text-sm truncate">{{ $teamAName }}</span>
            @if(!$multiple && count($headerAgentsA))
                <div class="flex gap-1.5 ml-3 shrink-0">
                    @foreach($headerAgentsA as $agent)
                        <x-public.agent-icon :agent="$agent" size="w-6 h-6" :rounded="false" />
                    @endforeach
                </div>
            @endif
        </div>

        <span class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em] px-2 shrink-0">{{ __('match.vs') }}</span>

        <div class="flex items-center gap-2.5 justify-end min-w-0">
            @if(!$multiple && count($headerAgentsB))
                <div class="flex gap-1.5 mr-3 shrink-0">
                    @foreach($headerAgentsB as $agent)
                        <x-public.agent-icon :agent="$agent" size="w-6 h-6" :rounded="false" />
                    @endforeach
                </div>
            @endif
            <span class="font-black italic text-white text-sm truncate">{{ $teamBName }}</span>
            <img src="{{ $teamBLogo ?? asset('storage/images/default-team.webp') }}"
                 alt="{{ $teamBName }}" loading="lazy"
                 class="w-8 h-8 rounded-lg object-contain bg-white/[0.03] shrink-0">
        </div>
    </div>

    <div
        x-data="{
            isDown: false, startX: 0, scrollLeft: 0,
            dragStart(e) { this.isDown = true; const x = e.touches ? e.touches[0].pageX : e.pageX; this.startX = x - $el.offsetLeft; this.scrollLeft = $el.scrollLeft; $el.classList.add('cursor-grabbing'); },
            dragMove(e) { if (!this.isDown) return; const x = e.touches ? e.touches[0].pageX : e.pageX; const walk = (x - this.startX); $el.scrollLeft = this.scrollLeft - walk; },
            dragEnd() { this.isDown = false; $el.classList.remove('cursor-grabbing'); }
        }"
        @mousedown="dragStart($event)"
        @mousemove="dragMove($event)"
        @mouseup="dragEnd()"
        @mouseleave="dragEnd()"
        @touchstart="dragStart($event)"
        @touchmove="dragMove($event)"
        @touchend="dragEnd()"
        class="hidden md:block overflow-x-auto cursor-grab select-none no-scrollbar relative w-full"
        role="table"
        aria-label="{{ __('match.stats.caption', ['team' => $teamAName]) }} / {{ __('match.stats.caption', ['team' => $teamBName]) }}"
    >
        <div class="w-full min-w-[1180px]">
            <div class="grid gap-5 px-3 py-2 text-[10px] uppercase tracking-wide font-black text-gray-500 border-b border-white/5"
                 style="grid-template-columns: {{ $gridCols }};" role="row">
                <div role="columnheader">{{ __('match.stats.agent_name') }}</div>
                <div role="columnheader">{{ __('match.stats.player') }}</div>
                <div aria-hidden="true"></div>
                <div role="columnheader" class="text-center" title="{{ __('match.stats.acs_full') }}">{{ __('match.stats.acs') }}</div>
                <div role="columnheader" class="text-center text-gray-300">K/D/A</div>
                <div role="columnheader" class="text-center" title="{{ __('match.stats.adr_full') }}">{{ __('match.stats.adr') }}</div>
                <div role="columnheader" class="text-center" title="{{ __('match.stats.kast_full') }}">{{ __('match.stats.kast_percentage') }}</div>
                <div role="columnheader" class="text-center">{{ __('match.stats.first_kills') }}-{{ __('match.stats.first_deaths') }}</div>
                <div role="columnheader" class="text-center">{{ __('match.stats.headshot_percentage') }}</div>
                <div aria-hidden="true"></div>
                <div role="columnheader" class="text-center">{{ __('match.stats.headshot_percentage') }}</div>
                <div role="columnheader" class="text-center">{{ __('match.stats.first_kills') }}-{{ __('match.stats.first_deaths') }}</div>
                <div role="columnheader" class="text-center" title="{{ __('match.stats.kast_full') }}">{{ __('match.stats.kast_percentage') }}</div>
                <div role="columnheader" class="text-center" title="{{ __('match.stats.adr_full') }}">{{ __('match.stats.adr') }}</div>
                <div role="columnheader" class="text-center text-gray-300">K/D/A</div>
                <div role="columnheader" class="text-center" title="{{ __('match.stats.acs_full') }}">{{ __('match.stats.acs') }}</div>
                <div aria-hidden="true"></div>
                <div role="columnheader" class="text-right">{{ __('match.stats.player') }}</div>
                <div role="columnheader" class="text-right">{{ __('match.stats.agent_name') }}</div>
            </div>

            <div role="rowgroup" class="divide-y divide-white/[0.02]">
                @for($i = 0; $i < $rowCount; $i++)
                    @php
                        $left = $statsA[$i] ?? null;
                        $right = $statsB[$i] ?? null;
                        $leftAgents = $rowAgents($left);
                        $rightAgents = $rowAgents($right);
                    @endphp
                    <div class="grid gap-5 px-3 py-2.5 items-center {{ $i % 2 === 1 ? 'bg-white/[0.015]' : '' }} hover:bg-white/[0.03] transition-colors"
                         style="grid-template-columns: {{ $gridCols }};" role="row">

                        <div role="cell" class="flex flex-wrap gap-1.5">
                            @foreach($leftAgents as $agent)
                                <x-public.agent-icon :agent="$agent" size="w-7 h-7" :rounded="false" />
                            @endforeach
                        </div>

                        <div role="cell" class="min-w-0 font-black italic uppercase text-[13px] text-white">
                            @if($left)
                                <div class="flex items-center gap-2 min-w-0">
                                    <a href="{{ route('players.show', [$left['player']['id'], str($left['player']['handle'] ?? '')->slug()]) }}" class="min-w-0 hover:text-[var(--brand-yellow)] transition-colors truncate">
                                        {{ $left['player']['handle'] ?? '---' }}
                                    </a>
                                    @if($maxAcs !== null && $left['acs'] === $maxAcs)
                                        <span class="text-[9px] font-black uppercase tracking-wide text-black bg-[var(--brand-yellow)] px-1.5 py-0.5 rounded shrink-0">{{ __('match.stats.mvp') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div aria-hidden="true"></div>

                        <div role="cell" class="text-center font-mono font-bold text-[13px] text-gray-300">{{ $left['acs'] ?? '' }}</div>

                        <div role="cell" class="text-center text-[12.5px] font-variant-numeric-tabular whitespace-nowrap">
                            @if($left)
                                <span class="text-white font-black">{{ $left['kills'] }}</span><span class="text-gray-700 mx-0.5">/</span><span class="text-red-500/70 font-black">{{ $left['deaths'] }}</span><span class="text-gray-700 mx-0.5">/</span><span class="text-gray-500 font-black">{{ $left['assists'] }}</span>
                            @endif
                        </div>

                        <div role="cell" class="text-center text-[12.5px] text-gray-300">{{ $left['adr'] ?? '' }}</div>

                        <div role="cell" class="text-center text-[12.5px] font-semibold text-gray-300">
                            {{ $left ? round($left['kast_percentage']).'%' : '' }}
                        </div>

                        <div role="cell" class="text-center text-[12.5px] font-semibold {{ $left ? $fkColor($left['first_kills'], $left['first_deaths']) : 'text-gray-600' }}">
                            {{ $left ? $left['first_kills'].'-'.$left['first_deaths'] : '' }}
                        </div>

                        <div role="cell" class="text-center text-[12.5px] text-gray-400">{{ $left ? round($left['headshot_percentage']).'%' : '' }}</div>

                        <div aria-hidden="true" class="w-px h-full bg-white/5 justify-self-center"></div>

                        <div role="cell" class="text-center text-[12.5px] text-gray-400">{{ $right ? round($right['headshot_percentage']).'%' : '' }}</div>

                        <div role="cell" class="text-center text-[12.5px] font-semibold {{ $right ? $fkColor($right['first_kills'], $right['first_deaths']) : 'text-gray-600' }}">
                            {{ $right ? $right['first_kills'].'-'.$right['first_deaths'] : '' }}
                        </div>

                        <div role="cell" class="text-center text-[12.5px] font-semibold text-gray-300">
                            {{ $right ? round($right['kast_percentage']).'%' : '' }}
                        </div>

                        <div role="cell" class="text-center text-[12.5px] text-gray-300">{{ $right['adr'] ?? '' }}</div>

                        <div role="cell" class="text-center text-[12.5px] font-variant-numeric-tabular whitespace-nowrap">
                            @if($right)
                                <span class="text-white font-black">{{ $right['kills'] }}</span><span class="text-gray-700 mx-0.5">/</span><span class="text-red-500/70 font-black">{{ $right['deaths'] }}</span><span class="text-gray-700 mx-0.5">/</span><span class="text-gray-500 font-black">{{ $right['assists'] }}</span>
                            @endif
                        </div>

                        <div role="cell" class="text-center font-mono font-bold text-[13px] text-gray-300">{{ $right['acs'] ?? '' }}</div>

                        <div aria-hidden="true"></div>

                        <div role="cell" class="min-w-0 text-right font-black italic uppercase text-[13px] text-white">
                            @if($right)
                                <div class="flex items-center justify-end gap-2 min-w-0">
                                    @if($maxAcs !== null && $right['acs'] === $maxAcs)
                                        <span class="text-[9px] font-black uppercase tracking-wide text-black bg-[var(--brand-yellow)] px-1.5 py-0.5 rounded shrink-0">{{ __('match.stats.mvp') }}</span>
                                    @endif
                                    <a href="{{ route('players.show', [$right['player']['id'], str($right['player']['handle'] ?? '')->slug()]) }}" class="min-w-0 hover:text-[var(--brand-yellow)] transition-colors truncate">
                                        {{ $right['player']['handle'] ?? '---' }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div role="cell" class="flex flex-wrap justify-end gap-1.5">
                            @foreach($rightAgents as $agent)
                                <x-public.agent-icon :agent="$agent" size="w-7 h-7" :rounded="false" />
                            @endforeach
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Mobile: one compact per-team table instead of the mirrored grid --}}
    <div class="md:hidden divide-y divide-white/5">
        @foreach([['name' => $teamAName, 'stats' => $statsA], ['name' => $teamBName, 'stats' => $statsB]] as $team)
            @php $gridColsMobile = '32px minmax(90px,140px) 36px 66px 34px 34px 46px 32px'; @endphp
            <div class="py-3">
                <div class="px-3 text-center text-[13px] font-black italic uppercase tracking-wide text-white mb-2">{{ $team['name'] }}</div>

                <div
                    x-data="{
                        isDown: false, startX: 0, scrollLeft: 0,
                        dragStart(e) { this.isDown = true; const x = e.touches ? e.touches[0].pageX : e.pageX; this.startX = x - $el.offsetLeft; this.scrollLeft = $el.scrollLeft; $el.classList.add('cursor-grabbing'); },
                        dragMove(e) { if (!this.isDown) return; const x = e.touches ? e.touches[0].pageX : e.pageX; const walk = (x - this.startX); $el.scrollLeft = this.scrollLeft - walk; },
                        dragEnd() { this.isDown = false; $el.classList.remove('cursor-grabbing'); }
                    }"
                    @mousedown="dragStart($event)"
                    @mousemove="dragMove($event)"
                    @mouseup="dragEnd()"
                    @mouseleave="dragEnd()"
                    @touchstart="dragStart($event)"
                    @touchmove="dragMove($event)"
                    @touchend="dragEnd()"
                    class="overflow-x-auto cursor-grab select-none no-scrollbar relative"
                >
                    <div class="min-w-[420px] px-3">
                        <div class="grid gap-2 items-center pb-1.5 text-[9px] uppercase tracking-wide font-black text-gray-500"
                             style="grid-template-columns: {{ $gridColsMobile }};">
                            <div></div>
                            <div>{{ __('match.stats.player') }}</div>
                            <div class="text-center" title="{{ __('match.stats.acs_full') }}">{{ __('match.stats.acs') }}</div>
                            <div class="text-center text-gray-300">K/D/A</div>
                            <div class="text-center" title="{{ __('match.stats.adr_full') }}">{{ __('match.stats.adr') }}</div>
                            <div class="text-center" title="{{ __('match.stats.kast_full') }}">{{ __('match.stats.kast_percentage') }}</div>
                            <div class="text-center">{{ __('match.stats.first_kills') }}-{{ __('match.stats.first_deaths') }}</div>
                            <div class="text-center">{{ __('match.stats.headshot_percentage') }}</div>
                        </div>

                        <div>
                            @foreach($team['stats'] as $i => $s)
                                @php
                                    $agents = $rowAgents($s);
                                    $isMvp = $maxAcs !== null && $s['acs'] === $maxAcs;
                                @endphp
                                <div class="grid gap-2 items-center py-2 pl-2 -ml-2 {{ $isMvp ? 'border-l-2 border-[var(--brand-yellow)]' : '' }}"
                                     style="grid-template-columns: {{ $gridColsMobile }};">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($agents as $agent)
                                            <x-public.agent-icon :agent="$agent" size="w-6 h-6" :rounded="false" />
                                        @endforeach
                                    </div>

                                    <div class="min-w-0 font-black italic uppercase text-[12px] text-white">
                                        <a href="{{ route('players.show', [$s['player']['id'], str($s['player']['handle'] ?? '')->slug()]) }}" class="min-w-0 block truncate hover:text-[var(--brand-yellow)] transition-colors">
                                            {{ $s['player']['handle'] ?? '---' }}
                                        </a>
                                    </div>

                                    <div class="text-center font-mono font-bold text-[12px] text-gray-300">{{ $s['acs'] }}</div>

                                    <div class="text-center text-[11px] whitespace-nowrap">
                                        <span class="text-white font-black">{{ $s['kills'] }}</span><span class="text-gray-700 mx-0.5">/</span><span class="text-red-500/70 font-black">{{ $s['deaths'] }}</span><span class="text-gray-700 mx-0.5">/</span><span class="text-gray-500 font-black">{{ $s['assists'] }}</span>
                                    </div>

                                    <div class="text-center text-[11px] text-gray-300">{{ $s['adr'] }}</div>

                                    <div class="text-center text-[11px] font-semibold text-gray-300">{{ round($s['kast_percentage']) }}%</div>

                                    <div class="text-center text-[11px] font-semibold {{ $fkColor($s['first_kills'], $s['first_deaths']) }}">{{ $s['first_kills'] }}-{{ $s['first_deaths'] }}</div>

                                    <div class="text-center text-[11px] text-gray-400">{{ round($s['headshot_percentage']) }}%</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
