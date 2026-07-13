{{--
    GC-Stats — Team stats table partial

    Renders a per-team table of player statistics (ACS, K/D/A, ADR, etc.)
    for a given map on the match detail page.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="bg-[#0d0d0d] rounded-2xl border border-white/5 shadow-2xl w-full overflow-hidden">
    <div class="bg-white/[0.02] p-4 border-b border-white/5">
        <h3 class="text-center text-[10px] font-black uppercase tracking-[0.4em] text-gray-400 group-hover:text-white transition-colors">
            {{ $teamName }}
        </h3>
    </div>

    <div
        x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
        @mousedown="if(window.innerWidth < 768) { isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft; $el.classList.add('cursor-grabbing') }"
        @mouseleave="isDown = false; $el.classList.remove('cursor-grabbing')"
        @mouseup="isDown = false; $el.classList.remove('cursor-grabbing')"
        @mousemove="if(!isDown) return; $event.preventDefault(); const x = $event.pageX - $el.offsetLeft; const walk = (x - startX) * 2; $el.scrollLeft = scrollLeft - walk;"
        class="block md:hidden overflow-x-auto cursor-grab select-none no-scrollbar relative"
    >
        <table class="w-full text-[10px] min-w-[600px] border-separate border-spacing-0">
            <caption class="sr-only">{{ __('match.stats.caption', ['team' => $teamName]) }}</caption>
            <thead class="bg-white/[0.01] text-gray-500 uppercase font-black tracking-widest">
            <tr>
                @if(!$multiple) <th scope="col" class="p-4 text-center border-b border-white/5">{{ __('match.stats.agent_name') }}</th> @endif
                <th scope="col" class="p-4 text-left sticky left-0 z-40 bg-[#0d0d0d] border-b border-white/5">{{ __("match.stats.player") }}</th>
                <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.acs_full') }}">ACS</th>
                <th scope="col" class="p-4 text-center border-b border-white/5 text-white">K/D/A</th>
                <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.adr_full') }}">ADR</th>
                <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.kast_full') }}">KAST</th>
                <th scope="col" class="p-4 text-center border-b border-white/5">FK/FD</th>
                <th scope="col" class="p-4 text-center border-b border-white/5">HS%</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.02]">
            @foreach($stats as $stat)
                <tr class="group hover:bg-white/[0.02] transition-colors">
                    @if(!$multiple)
                        <td class="p-3 text-center">
                            <img src="{{ asset('storage/agents/' . strtolower(str_replace('/', '', $stat['agent_name'])) . '.webp') }}"
                                 alt="{{ $stat['agent_name'] }}"
                                 class="w-7 h-7 rounded-sm border border-white/10 bg-black/40 mx-auto transition-all">
                        </td>
                    @endif
                    <td class="p-3 font-black text-white italic uppercase sticky left-0 z-30 bg-[#0d0d0d] group-hover:bg-white/[0.02] transition-colors">
                        <a href="{{ route('players.show', [$stat['player']['id'], str($stat['player']['handle'] ?? '')->slug()]) }}" class="hover:text-[var(--brand-yellow)] transition-colors block whitespace-nowrap">
                            {{ $stat['player']['handle'] ?? '---' }}
                        </a>
                    </td>
                    <td class="p-3 text-center font-mono font-bold text-gray-400">{{ $stat['acs'] }}</td>
                    <td class="p-3 text-center whitespace-nowrap">
                        <span class="text-white font-black">{{ $stat['kills'] }}</span>
                        <span class="text-gray-700 mx-0.5">/</span>
                        <span class="text-red-500/60 font-bold">{{ $stat['deaths'] }}</span>
                        <span class="text-gray-700 mx-0.5">/</span>
                        <span class="text-gray-500">{{ $stat['assists'] }}</span>
                    </td>
                    <td class="p-3 text-center text-gray-400 font-bold">{{ $stat['adr'] }}</td>
                    <td class="p-3 text-center {{ $stat['kast_percentage'] >= 75 ? 'text-green-500/70' : 'text-gray-600' }} font-bold">
                        {{ round($stat['kast_percentage']) }}%
                    </td>
                    <td class="p-3 text-center whitespace-nowrap">
                        <span class="text-green-500/50 font-bold">{{ $stat['first_kills'] }}</span>
                        <span class="text-gray-800 mx-1">-</span>
                        <span class="text-red-500/50 font-bold">{{ $stat['first_deaths'] }}</span>
                    </td>
                    <td class="p-3 text-center text-gray-500 font-black tracking-tighter">{{ round($stat['headshot_percentage']) }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div
        x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
        @mousedown="if(window.innerWidth < 768) { isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft; $el.classList.add('cursor-grabbing') }"
        @mouseleave="isDown = false; $el.classList.remove('cursor-grabbing')"
        @mouseup="isDown = false; $el.classList.remove('cursor-grabbing')"
        @mousemove="if(!isDown) return; $event.preventDefault(); const x = $event.pageX - $el.offsetLeft; const walk = (x - startX) * 2; $el.scrollLeft = scrollLeft - walk;"
        class="hidden md:block overflow-x-auto cursor-grab select-none no-scrollbar relative"
    >
        <table class="w-full text-[10px] min-w-[600px] border-separate border-spacing-0">
            <caption class="sr-only">{{ __('match.stats.caption', ['team' => $teamName]) }}</caption>
            <thead class="bg-white/[0.01] text-gray-500 uppercase font-black tracking-widest">
            <tr>
                @if(!($reverse ?? false))
                    @if(!$multiple) <th scope="col" class="p-4 text-center border-b border-white/5">{{ __('match.stats.agent_name') }}</th> @endif
                    <th scope="col" class="p-4 text-left sticky left-0 z-40 border-b border-white/5">{{ __("match.stats.player") }}</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.acs_full') }}">ACS</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5 text-white">K/D/A</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.adr_full') }}">ADR</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.kast_full') }}">KAST</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5">FK/FD</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5">HS%</th>
                @else
                    <th scope="col" class="p-4 text-center border-b border-white/5">HS%</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5">FK/FD</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.kast_full') }}">KAST</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.adr_full') }}">ADR</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5 text-white">K/D/A</th>
                    <th scope="col" class="p-4 text-center border-b border-white/5" title="{{ __('match.stats.acs_full') }}">ACS</th>
                    <th scope="col" class="p-4 text-right sticky right-0 z-40  border-b border-white/5">{{ __("match.stats.player") }}</th>
                    @if(!$multiple) <th scope="col" class="p-4 text-center border-b border-white/5">{{ __('match.stats.agent_name') }}</th> @endif
                @endif
            </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.02]">
            @foreach($stats as $stat)
                <tr class="group hover:bg-white/[0.02] transition-colors">
                    @if(!($reverse ?? false))
                        @if(!$multiple)
                            <td class="p-3 text-center">
                                <img src="{{ asset('storage/agents/' . strtolower(str_replace('/', '', $stat['agent_name'])) . '.webp') }}"
                                     alt="{{ $stat['agent_name'] }}"
                                     class="w-7 h-7 rounded-sm border border-white/10 bg-black/40 mx-auto  transition-all">
                            </td>
                        @endif
                        <td class="p-3 font-black text-white italic uppercase sticky left-0 z-30 bg-[#0d0d0d] group-hover:bg-white/[0.02] transition-colors">
                            <a href="{{ route('players.show', [$stat['player']['id'], str($stat['player']['handle'] ?? '')->slug()]) }}" class="hover:text-[var(--brand-yellow)] transition-colors block whitespace-nowrap">
                                {{ $stat['player']['handle'] ?? '---' }}
                            </a>
                        </td>
                        <td class="p-3 text-center font-mono font-bold text-gray-400">{{ $stat['acs'] }}</td>
                        <td class="p-3 text-center whitespace-nowrap">
                            <span class="text-white font-black">{{ $stat['kills'] }}</span>
                            <span class="text-gray-700 mx-0.5">/</span>
                            <span class="text-red-500/60 font-bold">{{ $stat['deaths'] }}</span>
                            <span class="text-gray-700 mx-0.5">/</span>
                            <span class="text-gray-500">{{ $stat['assists'] }}</span>
                        </td>
                        <td class="p-3 text-center text-gray-400 font-bold">{{ $stat['adr'] }}</td>
                        <td class="p-3 text-center {{ $stat['kast_percentage'] >= 75 ? 'text-green-500/70' : 'text-gray-600' }} font-bold">
                            {{ round($stat['kast_percentage']) }}%
                        </td>
                        <td class="p-3 text-center whitespace-nowrap">
                            <span class="text-green-500/50 font-bold">{{ $stat['first_kills'] }}</span>
                            <span class="text-gray-800 mx-1">-</span>
                            <span class="text-red-500/50 font-bold">{{ $stat['first_deaths'] }}</span>
                        </td>
                        <td class="p-3 text-center text-gray-500 font-black tracking-tighter">{{ round($stat['headshot_percentage']) }}%</td>
                    @else
                        <td class="p-3 text-center text-gray-500 font-black tracking-tighter">{{ round($stat['headshot_percentage']) }}%</td>
                        <td class="p-3 text-center whitespace-nowrap">
                            <span class="text-green-500/50 font-bold">{{ $stat['first_kills'] }}</span>
                            <span class="text-gray-800 mx-1">-</span>
                            <span class="text-red-500/50 font-bold">{{ $stat['first_deaths'] }}</span>
                        </td>
                        <td class="p-3 text-center {{ $stat['kast_percentage'] >= 75 ? 'text-green-500/70' : 'text-gray-600' }} font-bold">
                            {{ round($stat['kast_percentage']) }}%
                        </td>
                        <td class="p-3 text-center text-gray-400 font-bold">{{ $stat['adr'] }}</td>
                        <td class="p-3 text-center whitespace-nowrap">
                            <span class="text-white font-black">{{ $stat['kills'] }}</span>
                            <span class="text-gray-700 mx-0.5">/</span>
                            <span class="text-red-500/60 font-bold">{{ $stat['deaths'] }}</span>
                            <span class="text-gray-700 mx-0.5">/</span>
                            <span class="text-gray-500">{{ $stat['assists'] }}</span>
                        </td>
                        <td class="p-3 text-center font-mono font-bold text-gray-400">{{ $stat['acs'] }}</td>

                        <td class="p-3 font-black text-white italic uppercase text-right sticky right-0 z-30 bg-[#0d0d0d] group-hover:bg-white/[0.02] transition-colors">
                            <a href="{{ route('players.show', [$stat['player']['id'], str($stat['player']['handle'] ?? '')->slug()]) }}" class="hover:text-[var(--brand-yellow)] transition-colors block whitespace-nowrap ml-auto">
                                {{ $stat['player']['handle'] ?? '---' }}
                            </a>
                        </td>
                        @if(!$multiple)
                            <td class="p-3 text-center">
                                <img src="{{ asset('storage/agents/' . strtolower(str_replace('/', '', $stat['agent_name'])) . '.webp') }}"
                                     alt="{{ $stat['agent_name'] }}"
                                     class="w-7 h-7 rounded-sm border border-white/10 bg-black/40 mx-auto transition-all">
                            </td>
                        @endif
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
