{{--
    GC-Stats — Match card component

    Renders a compact match summary card (teams, logos, score and status)
    used in match lists such as the homepage and tournament pages.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['match' => []])

@php
    $matchStatus = $match['status'] ?? 'upcoming';
    $teamA = $match['team_a']['name'] ?? ($matchStatus == 'finished' ? 'BYE' : 'TBD');
    $teamB = $match['team_b']['name'] ?? ($matchStatus == 'finished' ? 'BYE' : 'TBD');
    $teamALogo = $match['team_a']['logo'] ?? asset('storage/images/default-team.webp');
    $teamBLogo = $match['team_b']['logo'] ?? asset('storage/images/default-team.webp');
    $matchAriaLabel = match($matchStatus) {
        'finished' => __('match.aria.finished', ['teamA' => $teamA, 'scoreA' => $match['team_a_score'] ?? 0, 'scoreB' => $match['team_b_score'] ?? 0, 'teamB' => $teamB]),
        'live'     => __('match.aria.live', ['teamA' => $teamA, 'scoreA' => $match['team_a_score'] ?? 0, 'scoreB' => $match['team_b_score'] ?? 0, 'teamB' => $teamB]),
        default    => __('match.aria.upcoming', ['teamA' => $teamA, 'teamB' => $teamB]),
    };
@endphp

<a href="{{ route('match.show', $match['id']) }}" class="block mb-2 md:mb-6 group" aria-label="{{ $matchAriaLabel }}">
    <div class="match-card relative bg-white/[0.02] border border-white/5 rounded-xl md:rounded-2xl overflow-hidden transition-all duration-300 group-hover:border-[var(--brand-yellow)]/30 group-hover:bg-white/[0.04] group-hover:-translate-y-1 group-hover:shadow-[0_20px_40px_rgba(0,0,0,0.6)]">
        <div class="px-3 py-1.5 md:px-5 md:py-3 border-b border-white/5 flex justify-between items-center bg-white/[0.01]">
            <div class="flex items-center gap-2 md:gap-3 min-w-0">
                <div class="w-0.5 h-2.5 md:w-1 md:h-3 bg-[var(--brand-yellow)] shrink-0"></div>
                <span class="text-[8px] md:text-[10px] font-black uppercase tracking-[0.15em] md:tracking-[0.2em] text-gray-400 truncate">
                   @if(isset($match['tournament']['name']) || isset($match['tournament_name']))
                        {{ $match['tournament']['name'] ?? $match['tournament_name'] }}
                        <span class="mx-1 md:mx-2 text-gray-700">/</span>
                    @endif

                    {{ $match['phase']['name'] ?? $match['phase_name'] ?? '' }} ({{ $match['round_name'] ?? '' }})
                </span>
            </div>

            <div class="shrink-0">
                @if($match['status'] == "live")
                    <div class="flex items-center gap-1.5 md:gap-2 px-2 py-0.5 md:px-3 md:py-1 bg-red-500/10 border border-red-500/20 rounded-full" role="status" aria-live="polite">
                        <span class="relative flex h-1.5 w-1.5 md:h-2 md:w-2" aria-hidden="true">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 md:h-2 md:w-2 bg-red-500"></span>
                        </span>
                        <span class="text-[8px] md:text-[10px] font-black text-red-500 uppercase tracking-widest">{{ __("index.live") }}</span>
                    </div>
                @else
                    @if(\App\Helpers\PivotDate::isUnknown($match['scheduled_at'] ?? null))
                        <div class="text-[8px] md:text-[10px] font-black text-gray-500 uppercase tracking-tighter">
                            {{ __('match.unknown_date') }}
                        </div>
                    @else
                        <div class="text-[8px] md:text-[10px] font-black text-gray-500 uppercase tracking-tighter" data-utc-datetime="{{ \Carbon\Carbon::parse($match['scheduled_at'], 'UTC')->toIso8601String() }}">
                            <span class="js-match-date">{{ \Carbon\Carbon::parse($match['scheduled_at'])->translatedFormat('d M Y') }}</span>
                            <span class="js-match-time text-[var(--brand-yellow)] ml-1 tracking-normal italic">{{ \Carbon\Carbon::parse($match['scheduled_at'])->translatedFormat('H:i') }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="flex flex-row items-center p-2 md:p-5 relative">
            <div class="flex items-center gap-1.5 md:gap-4 flex-1 w-full min-w-0 justify-end group/teamA">
                <span class="font-black text-[10px] md:text-[15px] uppercase text-white transition-all group-hover/teamA:text-[var(--brand-yellow)] text-right truncate">
                    {{ $teamA }}
                </span>
                <div class="relative shrink-0">
                    <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-xl transition-opacity"></div>
                    <img src="{{ $teamALogo }}" class="w-6 h-6 md:w-16 md:h-16 object-contain relative z-10 filter drop-shadow-2xl" alt="{{ $teamA }}">
                </div>
            </div>

            <div class="mx-2 md:mx-12 shrink-0 relative z-10">
                @if($match["status"] == "finished")
                    <div class="flex items-center bg-black/40 p-1 rounded-xl border border-white/5 backdrop-blur-sm shadow-2xl">
                        <div class="w-14 h-14 flex items-center justify-center text-2xl font-black {{ $match["team_a_score"] > $match["team_b_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} italic">
                            {{ $match["team_a_score"] == -1 ? "FF" : $match["team_a_score"] }}
                        </div>
                        <div class="w-[1px] h-8 bg-white/10"></div>
                        <div class="w-14 h-14 flex items-center justify-center text-2xl font-black {{ $match["team_b_score"] > $match["team_a_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} italic">
                            {{ $match["team_b_score"] == -1 ? "FF" : $match["team_b_score"] }}
                        </div>
                    </div>
                @elseif($match["status"] == "upcoming")
                    <div class="flex items-center bg-black/40 p-0.5 md:p-1 rounded-lg md:rounded-xl border border-white/5 backdrop-blur-sm shadow-2xl">
                        <div class="w-7 h-7 md:w-14 md:h-14 flex items-center justify-center text-xs md:text-2xl font-black text-white italic">
                           VS
                        </div>
                    </div>
                @else
                    <div class="flex items-center bg-black/40 p-0.5 md:p-1 rounded-lg md:rounded-xl border border-white/5 backdrop-blur-sm shadow-2xl">
                        <div class="w-7 h-7 md:w-14 md:h-14 flex items-center justify-center text-xs md:text-2xl font-black {{ $match["team_a_score"] > $match["team_b_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} italic">
                            {{ $match["team_a_score"] }}
                        </div>
                        <div class="w-[1px] h-4 md:h-8 bg-white/10"></div>
                        <div class="w-7 h-7 md:w-14 md:h-14 flex items-center justify-center text-xs md:text-2xl font-black {{ $match["team_b_score"] > $match["team_a_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} italic">
                            {{ $match["team_b_score"] }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-1.5 md:gap-4 flex-1 w-full min-w-0 justify-start group/teamB">
                <div class="relative shrink-0">
                    <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-xl transition-opacity"></div>
                    <img src="{{ $teamBLogo }}" class="w-6 h-6 md:w-16 md:h-16 object-contain relative z-10 filter drop-shadow-2xl drop-shadow-[0_0_8px_color-mix(in_srgb,var(--brand-yellow),transparent_95%)]" alt="{{ $teamB }}">
                </div>
                <span class="font-black text-[10px] md:text-[15px] uppercase text-white transition-all group-hover/teamB:text-[var(--brand-yellow)] text-left truncate">
                    {{ $teamB }}
                </span>
            </div>
        </div>

        <div class="absolute top-0 right-0 w-32 h-32 bg-white/[0.02] -rotate-45 translate-x-16 -translate-y-16 pointer-events-none"></div>
    </div>
</a>
