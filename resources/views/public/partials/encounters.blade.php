{{--
    GC-Stats — Encounters (head-to-head match history)

    Compact list of prior meetings between the two teams in the current
    match, across every tournament, most recent first. Fed by
    MatchController::buildEncounters() (App\Support\MatchPresenter, scores
    presented from the current match's team_a perspective).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['encounters'])

@php
    $teamA = $match['team_a_data'] ?? null;
    $teamB = $match['team_b_data'] ?? null;
@endphp

<div>
    <div class="flex items-center justify-between gap-4 mb-4">
        <span class="text-[9px] font-black text-gray-600 uppercase tracking-[0.3em]">{{ __('match.encounters.title') }}</span>
    </div>

    <div class="bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden">
        <div class="p-4 md:p-5 border-b border-white/5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <img src="{{ $teamA['logo'] ?? asset('storage/images/default-team.webp') }}" alt="{{ $teamA['name'] ?? '' }}" class="w-8 h-8 object-contain shrink-0">
                <span class="text-xs font-black uppercase tracking-wide text-white truncate">{{ $teamA['short_name'] ?? $teamA['name'] ?? 'TBD' }}</span>
            </div>

            <span class="text-xs font-black text-white tracking-tight shrink-0">
                {{ $encounters['team_a_wins'] }} – {{ $encounters['team_b_wins'] }}
            </span>

            <div class="flex items-center gap-3 min-w-0 justify-end">
                <span class="text-xs font-black uppercase tracking-wide text-white truncate">{{ $teamB['short_name'] ?? $teamB['name'] ?? 'TBD' }}</span>
                <img src="{{ $teamB['logo'] ?? asset('storage/images/default-team.webp') }}" alt="{{ $teamB['name'] ?? '' }}" class="w-8 h-8 object-contain shrink-0">
            </div>
        </div>

        <div class="p-2 md:p-3">
        @forelse($encounters['matches'] as $enc)
            <a href="{{ route('match.show', $enc['id']) }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/[0.03] transition-colors">
                <div class="w-1 h-8 rounded-full shrink-0 {{ $enc['result'] == 'win' ? 'bg-[var(--brand-yellow)]' : ($enc['result'] == 'loss' ? 'bg-white/10' : 'bg-gray-600') }}"></div>

                <div class="min-w-0 flex-1">
                    <div class="text-[9px] font-black uppercase tracking-widest text-gray-500 truncate group-hover:text-gray-300 transition-colors">
                        {{ $enc['tournament_name'] ?? '—' }}
                    </div>
                    <div class="text-[9px] text-gray-600" data-utc-datetime="{{ \Carbon\Carbon::parse($enc['scheduled_at'], 'UTC')->toIso8601String() }}">
                        <span class="js-match-date">{{ \Carbon\Carbon::parse($enc['scheduled_at'])->translatedFormat('d M Y') }}</span>
                    </div>
                </div>

                <div class="shrink-0 flex items-center gap-1.5 font-black text-sm tracking-tighter">
                    <span class="{{ $enc['team_a_score'] > $enc['team_b_score'] ? 'text-[var(--brand-yellow)]' : 'text-white' }}">
                        {{ $enc['team_a_score'] == -1 ? 'FF' : $enc['team_a_score'] }}
                    </span>
                    <span class="text-gray-700">–</span>
                    <span class="{{ $enc['team_b_score'] > $enc['team_a_score'] ? 'text-[var(--brand-yellow)]' : 'text-white' }}">
                        {{ $enc['team_b_score'] == -1 ? 'FF' : $enc['team_b_score'] }}
                    </span>
                </div>
            </a>
        @empty
            <p class="text-center text-gray-500 py-6 text-xs px-4">{{ __('match.encounters.empty') }}</p>
        @endforelse
        </div>
    </div>
</div>
