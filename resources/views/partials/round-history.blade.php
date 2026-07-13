{{--
    GC-Stats — Round history partial

    Renders the round-by-round outcome history for a map (win/loss icons
    per round) on the match detail page.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php $roundCount = count($map['rounds']); @endphp
<section class="w-full bg-[#0d0d0d] rounded-2xl border border-white/5 shadow-2xl overflow-hidden" aria-label="{{ __('match.round_history') }}">
    {{-- Mobile layout: teams stacked with side colors + wrapping round grid --}}
    <div class="md:hidden p-4 space-y-3">
        <div class="grid grid-cols-[1.75rem_1fr] items-center gap-2">
            <div class="w-7 h-7 flex-shrink-0 flex items-center justify-center rounded-lg border border-blue-500/30 bg-blue-500/10 shadow-[0_0_10px_rgba(59,130,246,0.4)] p-1">
                <img alt="{{ $match['team_a']['name'] ?? 'TBD' }}" src="{{ $match['team_a']['logo'] ?? asset('storage/images/default-team.webp') }}" class="w-full h-full object-contain">
            </div>
            <span class="min-w-0 text-center text-[11px] font-black uppercase text-white truncate">{{ $match['team_a']['name'] ?? 'TBD' }}</span>
        </div>

        <div class="grid grid-cols-[1.75rem_1fr] items-center gap-2">
            <div class="w-7 h-7 flex-shrink-0 flex items-center justify-center rounded-lg border border-red-500/30 bg-red-500/10 shadow-[0_0_10px_rgba(239,68,68,0.4)] p-1">
                <img alt="{{ $match['team_b']['name'] ?? 'TBD' }}" src="{{ $match['team_b']['logo'] ?? asset('storage/images/default-team.webp') }}" class="w-full h-full object-contain">
            </div>
            <span class="min-w-0 text-center text-[11px] font-black uppercase text-white truncate">{{ $match['team_b']['name'] ?? 'TBD' }}</span>
        </div>

        <div class="flex flex-wrap justify-center gap-2">
            @foreach($map["rounds"] as $round)
                @php
                    $wonByA = $round['winning_team'] === $match['team_a']['id'];
                    $wonByB = $round['winning_team'] === $match['team_b']['id'];
                    $winType = $round["win_type"] ? str_replace(' ', '_', strtolower($round["win_type"])) : 'timeout';
                @endphp
                <div class="w-6 h-6 aspect-square rounded-md flex items-center justify-center transition-all duration-300
                    {{ $wonByA ? 'bg-blue-500/10 border border-blue-500/30 shadow-[0_0_10px_rgba(59,130,246,0.1)]' : ($wonByB ? 'bg-red-500/10 border border-red-500/30 shadow-[0_0_10px_rgba(239,68,68,0.1)]' : 'bg-black/40 border border-white/5 opacity-40') }}">

                    @if($wonByA || $wonByB)
                        <img src="{{ asset('storage/icons/wins/' . $winType . '.webp') }}"
                             alt="{{ $round['win_type'] ?? 'timeout' }}"
                             class="w-3/4 h-3/4 object-contain brightness-110 {{ $wonByA ? 'drop-shadow-[0_0_5px_rgba(59,130,246,0.5)]' : 'drop-shadow-[0_0_5px_rgba(239,68,68,0.5)]' }}">
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="hidden md:block p-6 space-y-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-lg border border-blue-500/30 bg-blue-500/10 shadow-[0_0_10px_rgba(59,130,246,0.4)] p-1.5">
                <img alt="{{ $match['team_a']['name'] ?? 'TBD' }}" src="{{ $match['team_a']['logo'] ?? asset('storage/images/default-team.webp') }}" class="w-full h-full object-contain">
            </div>

            <div class="grid items-end gap-1 flex-grow" style="grid-template-columns: repeat({{ $roundCount }}, minmax(0, 1fr));">
                @foreach($map["rounds"] as $round)
                    <div class="relative flex flex-col items-center gap-1 group">
                        <span class="text-[8px] text-gray-600 font-black font-mono leading-none transition-colors group-hover:text-gray-400">
                            {{ str_pad($round["round_number"], 2, '0', STR_PAD_LEFT) }}
                        </span>

                        <div class="w-6 h-6 aspect-square rounded-md flex items-center justify-center transition-all duration-300
                            {{ $round['winning_team'] === $match['team_a']['id'] ? 'bg-blue-500/10 border border-blue-500/30 shadow-[0_0_10px_rgba(59,130,246,0.1)]' : 'bg-black/40 border border-white/5 opacity-40' }}">

                            @if($round["winning_team"] === $match['team_a']["id"])
                                @php $winType = $round["win_type"] ? str_replace(' ', '_', strtolower($round["win_type"])) : 'timeout'; @endphp
                                <img src="{{ asset('storage/icons/wins/' . $winType . '.webp') }}"
                                     alt="{{ $round['win_type'] ?? 'timeout' }}"
                                     class="w-3/4 h-3/4 object-contain brightness-110 drop-shadow-[0_0_5px_rgba(59,130,246,0.5)]">
                            @endif
                        </div>

                        @if($loop->iteration == 12 || $loop->iteration == 24)
                            <div class="absolute -right-1 top-0 bottom-0 w-px bg-white/10"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="w-12 hidden md:block"></div>
        </div>

        <div class="relative h-px w-full">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-1.5 h-1.5 rotate-45 border border-white/20 bg-[#0d0d0d]"></div>
        </div>

        <div class="flex items-center gap-3">
            <div class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-lg border border-red-500/30 bg-red-500/10 shadow-[0_0_10px_rgba(239,68,68,0.4)] p-1.5">
                <img alt="{{ $match['team_b']['name'] ?? 'TBD' }}" src="{{ $match['team_b']['logo'] ?? asset('storage/images/default-team.webp') }}" class="w-full h-full object-contain">
            </div>

            <div class="grid items-start gap-1 flex-grow" style="grid-template-columns: repeat({{ $roundCount }}, minmax(0, 1fr));">
                @foreach($map["rounds"] as $round)
                    <div class="relative flex flex-col items-center gap-1 group">
                        <div class="w-6 h-6 aspect-square rounded-md flex items-center justify-center transition-all duration-300
                            {{ $round['winning_team'] === $match['team_b']['id'] ? 'bg-red-500/10 border border-red-500/30 shadow-[0_0_10px_rgba(239,68,68,0.1)]' : 'bg-black/40 border border-white/5 opacity-40' }}">

                            @if($round["winning_team"] === $match['team_b']["id"])
                                @php $winType = $round["win_type"] ? str_replace(' ', '_', strtolower($round["win_type"])) : 'timeout'; @endphp
                                <img src="{{ asset('storage/icons/wins/' . $winType . '.webp') }}"
                                     alt="{{ $round['win_type'] ?? 'timeout' }}"
                                     class="w-3/4 h-3/4 object-contain brightness-110 drop-shadow-[0_0_5px_rgba(239,68,68,0.5)]">
                            @endif
                        </div>

                        @if($loop->iteration == 12 || $loop->iteration == 24)
                            <div class="absolute -right-1 top-0 bottom-0 w-px bg-white/10"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="w-12 hidden md:block"></div>
        </div>
    </div>
</section>
