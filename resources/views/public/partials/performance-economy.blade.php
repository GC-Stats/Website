{{--
    GC-Stats — Performance + economy partial

    Ported from the "Match Stats" Claude Design mockup's second block: one
    card holding the mirrored multi-kill breakdown (SHF/2K/3K/4K/5K per
    player) followed by a two-column economy summary (buy-type win rates as
    full-width bars). The header bar mirrors the scoreboard's own header
    (team logo/name left and right, label centered) instead of the mockup's
    plain text title.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $buyColors = [
        'eco' => 'bg-gray-500',
        'semi_eco' => 'bg-blue-400',
        'semi_buy' => 'bg-cyan-400',
        'full_buy' => 'bg-green-400',
    ];

    $cellColor = function ($v) {
        if ($v == 0) {
            return 'text-gray-600';
        }
        if ($v >= 3) {
            return 'text-green-400';
        }

        return 'text-gray-100';
    };

    $rowCount = max(count($statsA), count($statsB));
    $hasEcoA = collect($ecoSummary['team_a'] ?? [])->sum('total') > 0;
    $hasEcoB = collect($ecoSummary['team_b'] ?? [])->sum('total') > 0;
    $perfGridCols = '1fr repeat(5,56px) 24px repeat(5,56px) 1fr';
@endphp

<div class="bg-[#0d0d0d] rounded-2xl border border-white/5 shadow-2xl overflow-hidden">
    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 px-5 py-4 bg-white/[0.02] border-b border-white/5">
        <div class="min-w-0">
            <span class="block font-black italic text-white text-sm truncate">{{ $teamAName }}</span>
        </div>

        <span class="text-center text-[11px] font-black text-gray-300 uppercase tracking-[0.2em] px-2 shrink-0 whitespace-nowrap">{{ __('match.performance') }}</span>

        <div class="min-w-0 text-right">
            <span class="block font-black italic text-white text-sm truncate">{{ $teamBName }}</span>
        </div>
    </div>

    <div class="p-5">
        @if(!empty($performance))
            <div class="hidden md:block overflow-x-auto no-scrollbar">
                <div class="min-w-[640px]">
                    <div class="grid items-center px-1 py-2 text-[11px] uppercase text-gray-500 font-semibold"
                         style="grid-template-columns: {{ $perfGridCols }};">
                        <div>{{ __('match.stats.player') }}</div>
                        <div class="text-center">SHF</div>
                        <div class="text-center">2K</div>
                        <div class="text-center">3K</div>
                        <div class="text-center">4K</div>
                        <div class="text-center">5K</div>
                        <div></div>
                        <div class="text-center">5K</div>
                        <div class="text-center">4K</div>
                        <div class="text-center">3K</div>
                        <div class="text-center">2K</div>
                        <div class="text-center">SHF</div>
                        <div class="text-right">{{ __('match.stats.player') }}</div>
                    </div>

                    @for($i = 0; $i < $rowCount; $i++)
                        @php
                            $left = $statsA[$i] ?? null;
                            $right = $statsB[$i] ?? null;
                            $pfA = $left ? ($performance[$left['player_id']] ?? null) : null;
                            $pfB = $right ? ($performance[$right['player_id']] ?? null) : null;
                        @endphp
                        <div class="grid items-center px-1 py-2 rounded-md {{ $i % 2 === 1 ? 'bg-white/[0.015]' : '' }}"
                             style="grid-template-columns: {{ $perfGridCols }};">
                            <div class="font-black text-[13px] text-white italic truncate">{{ $left['player']['handle'] ?? '-' }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfA['sheriff_kills'] ?? 0) }}">{{ $pfA['sheriff_kills'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfA['2k'] ?? 0) }}">{{ $pfA['2k'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfA['3k'] ?? 0) }}">{{ $pfA['3k'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfA['4k'] ?? 0) }}">{{ $pfA['4k'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfA['5k'] ?? 0) }}">{{ $pfA['5k'] ?? 0 }}</div>
                            <div></div>
                            <div class="text-center text-[13px] {{ $cellColor($pfB['5k'] ?? 0) }}">{{ $pfB['5k'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfB['4k'] ?? 0) }}">{{ $pfB['4k'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfB['3k'] ?? 0) }}">{{ $pfB['3k'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfB['2k'] ?? 0) }}">{{ $pfB['2k'] ?? 0 }}</div>
                            <div class="text-center text-[13px] {{ $cellColor($pfB['sheriff_kills'] ?? 0) }}">{{ $pfB['sheriff_kills'] ?? 0 }}</div>
                            <div class="font-black text-[13px] text-white italic text-right truncate">{{ $right['player']['handle'] ?? '-' }}</div>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Mobile: one compact per-team block instead of the mirrored grid --}}
            <div class="md:hidden space-y-4">
                @foreach([['name' => $teamAName, 'stats' => $statsA], ['name' => $teamBName, 'stats' => $statsB]] as $team)
                    <div>
                        <div class="text-center text-[13px] font-black italic uppercase tracking-wide text-white mb-2">{{ $team['name'] }}</div>

                        <div class="grid items-center px-1 py-1.5 text-[10px] uppercase text-gray-500 font-semibold"
                             style="grid-template-columns: minmax(0,1fr) repeat(5,40px);">
                            <div>{{ __('match.stats.player') }}</div>
                            <div class="text-center">SHF</div>
                            <div class="text-center">2K</div>
                            <div class="text-center">3K</div>
                            <div class="text-center">4K</div>
                            <div class="text-center">5K</div>
                        </div>

                        @foreach($team['stats'] as $i => $s)
                            @php $pf = $performance[$s['player_id']] ?? null; @endphp
                            <div class="grid items-center px-1 py-2 rounded-md {{ $i % 2 === 1 ? 'bg-white/[0.015]' : '' }}"
                                 style="grid-template-columns: minmax(0,1fr) repeat(5,40px);">
                                <div class="font-black text-[12px] text-white italic truncate">{{ $s['player']['handle'] ?? '-' }}</div>
                                <div class="text-center text-[12px] {{ $cellColor($pf['sheriff_kills'] ?? 0) }}">{{ $pf['sheriff_kills'] ?? 0 }}</div>
                                <div class="text-center text-[12px] {{ $cellColor($pf['2k'] ?? 0) }}">{{ $pf['2k'] ?? 0 }}</div>
                                <div class="text-center text-[12px] {{ $cellColor($pf['3k'] ?? 0) }}">{{ $pf['3k'] ?? 0 }}</div>
                                <div class="text-center text-[12px] {{ $cellColor($pf['4k'] ?? 0) }}">{{ $pf['4k'] ?? 0 }}</div>
                                <div class="text-center text-[12px] {{ $cellColor($pf['5k'] ?? 0) }}">{{ $pf['5k'] ?? 0 }}</div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        @if($hasEcoA || $hasEcoB)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 {{ !empty($performance) ? 'mt-6' : '' }}">
                @if($hasEcoA)
                    <div class="bg-white/[0.02] rounded-xl p-4 border border-white/5">
                        <div class="flex items-center gap-2 mb-3">
                            <img src="{{ $teamALogo ?? asset('storage/images/default-team.webp') }}" alt="" class="w-5 h-5 rounded object-contain shrink-0">
                            <span class="text-[13px] font-black text-white truncate">{{ __('match.economy', ['team' => $teamAName]) }}</span>
                        </div>

                        @foreach($ecoSummary['team_a'] as $tierKey => $tier)
                            <div class="mb-2.5 last:mb-0">
                                <div class="flex justify-between text-[12px] mb-1">
                                    <span class="text-gray-400">{{ $tier['label'] }}</span>
                                    <span class="font-bold text-white">{{ $tier['win'] }}<span class="text-gray-600">/</span>{{ $tier['total'] }}</span>
                                </div>
                                <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full {{ $buyColors[$tierKey] ?? 'bg-gray-500' }}" style="width: {{ $tier['total'] > 0 ? round(($tier['win'] / $tier['total']) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($hasEcoB)
                    <div class="bg-white/[0.02] rounded-xl p-4 border border-white/5">
                        <div class="flex items-center gap-2 mb-3">
                            <img src="{{ $teamBLogo ?? asset('storage/images/default-team.webp') }}" alt="" class="w-5 h-5 rounded object-contain shrink-0">
                            <span class="text-[13px] font-black text-white truncate">{{ __('match.economy', ['team' => $teamBName]) }}</span>
                        </div>

                        @foreach($ecoSummary['team_b'] as $tierKey => $tier)
                            <div class="mb-2.5 last:mb-0">
                                <div class="flex justify-between text-[12px] mb-1">
                                    <span class="text-gray-400">{{ $tier['label'] }}</span>
                                    <span class="font-bold text-white">{{ $tier['win'] }}<span class="text-gray-600">/</span>{{ $tier['total'] }}</span>
                                </div>
                                <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full {{ $buyColors[$tierKey] ?? 'bg-gray-500' }}" style="width: {{ $tier['total'] > 0 ? round(($tier['win'] / $tier['total']) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
