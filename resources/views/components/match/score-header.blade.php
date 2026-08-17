{{--
    GC-Stats — Match score header

    The tournament link + team A/B logos/names + score (or vs/live) block —
    extracted from public/match.blade.php so the exact same visual can be
    reused anywhere a match needs showing, e.g. a forum embed card (see
    resources/views/components/forum/embed-card.blade.php). $match is the
    same array shape MatchController builds (see Matchs::toScoreHeaderArray(),
    the single source of truth both callers use for the team_a_data/
    team_b_data/tournament/tournament_phase shape).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['match'])

@php
    $teamAName = $match['team_a_data']['name'] ?? ($match['status'] == 'finished' ? __('match.team_bye') : __('match.team_tbd'));
    $teamBName = $match['team_b_data']['name'] ?? ($match['status'] == 'finished' ? __('match.team_bye') : __('match.team_tbd'));
@endphp

<div class="relative mb-6 flex flex-col items-center">
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="w-full h-[1px] bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
    </div>

    <a href="{{ isset($match['tournament']['id']) ? route('tournaments.show', [$match['tournament']['id'], str($match['tournament']['name'] ?? $match['tournament_name'] ?? '')->slug()]) : '#' }}"
       class="group relative bg-bg-main px-8 py-1 transition-all">

        <div class="flex flex-col items-center gap-0.5">
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em] group-hover:text-[var(--brand-yellow)] transition-colors">
                {{ $match['tournament']['name'] ?? $match['tournament_name'] ?? __('match.unknown_tournament') }}
            </span>

            <span class="text-[9px] font-bold text-[var(--brand-yellow)]/60 uppercase tracking-widest">
                {{ $match['tournament_phase']['name'] ?? $match['phase_name'] ?? null }}
            </span>
        </div>

        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-[1px] bg-[var(--brand-yellow)] transition-all duration-300 group-hover:w-1/2 opacity-50"></div>
    </a>
</div>

<div class="relative overflow-hidden bg-gradient-to-b from-white/[0.03] to-transparent border border-white/5 rounded-2xl p-4 md:p-6 shadow-2xl backdrop-blur-sm">
    <div class="absolute inset-0 opacity-[0.02] pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>

    <div class="relative flex flex-col md:flex-row items-center justify-between gap-6 md:gap-4">

        <a href="{{ $match['team_a_id'] ? route('teams.show', [$match['team_a_id'], str($teamAName)->slug()]) : '#' }}"
           class="flex flex-col md:flex-row items-center gap-3 md:gap-4 flex-1 min-w-0 group justify-center md:justify-end">

            <div class="relative order-2 md:order-1 text-center md:text-right min-w-0">
                <h3 class="font-black text-lg md:text-xl italic text-white group-hover:text-[var(--brand-yellow)] transition-colors tracking-tight leading-none truncate">
                    {{ $teamAName }}
                </h3>
            </div>

            <div class="relative order-1 md:order-2 shrink-0">
                <div class="absolute inset-0 bg-[var(--brand-yellow)]/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <img src="{{ $match['team_a_data']['logo'] ?? asset('storage/images/default-team.webp') }}"
                     alt="{{ $teamAName }}"
                     class="relative w-12 h-12 md:w-16 md:h-16 object-contain transition-transform duration-500 group-hover:scale-110">
            </div>
        </a>

        <div class="flex flex-col items-center shrink-0 z-10 px-4">
            @if(!empty($match['patch']))
                <span class="mb-3 text-[8px] font-medium text-gray-600 uppercase tracking-widest">
                    {{ __('match.patch', ['patch' => $match['patch']]) }}
                </span>
            @endif

            <div class="relative group">
                <div class="absolute -inset-4 bg-white/[0.02] rounded-full blur-2xl"></div>

                <div class="relative flex items-center justify-center gap-3 bg-black/60 backdrop-blur-xl border border-white/10 px-6 py-3 rounded-2xl shadow-2xl overflow-hidden">
                    @if($match["status"] == "finished")
                        <span class="sr-only">{{ __('match.score_label', ['teamA' => $teamAName, 'scoreA' => $match['team_a_score'], 'scoreB' => $match['team_b_score'], 'teamB' => $teamBName]) }}</span>
                        <span class="text-3xl md:text-4xl font-black {{ $match["team_a_score"] > $match["team_b_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter" aria-hidden="true">{{ $match["team_a_score"] == -1 ? 'FF' : $match["team_a_score"] }}</span>
                        <div class="w-[1px] h-8 bg-white/10" aria-hidden="true"></div>
                        <span class="text-3xl md:text-4xl font-black {{ $match["team_b_score"] > $match["team_a_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter" aria-hidden="true">{{ $match["team_b_score"] == -1 ? 'FF' : $match["team_b_score"] }}</span>
                    @elseif($match["status"] == "upcoming")
                        <span class="text-3xl md:text-4xl font-black text-white tracking-tighter" aria-label="{{ __('match.upcoming') }}">VS</span>
                    @else
                        <div class="flex flex-col items-center" role="status" aria-live="polite">
                            <span class="text-sm font-black text-green-500 animate-pulse tracking-[0.3em] mb-1 uppercase">{{ __('match.status.live') }}</span>
                            <span class="sr-only">{{ __('match.score_label', ['teamA' => $teamAName, 'scoreA' => $match['team_a_score'], 'scoreB' => $match['team_b_score'], 'teamB' => $teamBName]) }}</span>
                            <div class="flex items-center gap-2" aria-hidden="true">
                                <span class="text-3xl md:text-4xl font-black {{ $match["team_a_score"] > $match["team_b_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter">{{ $match["team_a_score"] == -1 ? 'FF' : $match["team_a_score"] }}</span>
                                <div class="w-[1px] h-8 bg-white/10"></div>
                                <span class="text-3xl md:text-4xl font-black {{ $match["team_b_score"] > $match["team_a_score"] ? 'text-[var(--brand-yellow)]' : 'text-white' }} tracking-tighter">{{ $match["team_b_score"] == -1 ? 'FF' : $match["team_b_score"] }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if(\App\Helpers\PivotDate::isUnknown($match['scheduled_at'] ?? null))
                <div class="mt-3 flex flex-col items-center gap-1">
                    <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">
                        {{ __('match.unknown_date') }}
                    </span>
                </div>
            @else
                <div class="mt-3 flex flex-col items-center gap-1" data-utc-datetime="{{ \Carbon\Carbon::parse($match['scheduled_at'], 'UTC')->toIso8601String() }}">
                    <span class="js-match-date text-[10px] font-black text-white/40 uppercase tracking-widest">
                        {{ \Carbon\Carbon::parse($match['scheduled_at'])->translatedFormat('d M Y') }}
                    </span>
                    <span class="js-match-time text-[11px] font-black text-[var(--brand-yellow)] tracking-tighter">
                        {{ \Carbon\Carbon::parse($match['scheduled_at'])->format('H:i') }}
                    </span>
                </div>
            @endif
        </div>

        <a href="{{ $match['team_b_id'] ? route('teams.show', [$match['team_b_id'], str($teamBName)->slug()]) : '#' }}"
           class="flex flex-col md:flex-row items-center gap-3 md:gap-4 flex-1 min-w-0 group justify-center md:justify-start">

            <div class="relative shrink-0">
                <div class="absolute inset-0 bg-[var(--brand-yellow)]/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <img src="{{ $match['team_b_data']['logo'] ?? asset('storage/images/default-team.webp') }}"
                     alt="{{ $teamBName }}"
                     class="relative w-12 h-12 md:w-16 md:h-16 object-contain transition-transform duration-500 group-hover:scale-110">
            </div>

            <div class="text-center md:text-left min-w-0">
                <h3 class="font-black text-lg md:text-xl italic text-white group-hover:text-[var(--brand-yellow)] transition-colors tracking-tight leading-none truncate">
                    {{ $teamBName }}
                </h3>
            </div>
        </a>
    </div>

    {{ $slot ?? '' }}
</div>
