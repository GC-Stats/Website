{{--
    GC-Stats — Phase leaderboard

    One row per team, 1st to last, showing place / points / cash prize /
    destination — sourced from the phase's qualification rules:
      - swiss/round_robin: every team from the standings, rank = its row
        index, matched against the phase's rank-range rules.
      - bracket: one row per match-outcome rule that carries an explicit
        placement (winner/loser of a specific match), sorted by that
        placement. Rules that only advance a team to another phase without
        an explicit placement number aren't final standings, so they don't
        produce a row here (the bracket's "half-match" card already shows
        that advancement).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['phase' => null, 'teams' => []])

@php
    $format = $phase['format'] ?? null;
    $rows = collect();

    if (in_array($format, \App\Models\TournamentPhase::RANK_BASED_FORMATS, true)) {
        $standings = \App\Support\TournamentStandings::compute($phase['matches'] ?? [], $teams, $format === 'swiss_buchholz');
        $qualifications = collect($phase['qualifications'] ?? []);

        $rows = $standings->values()->map(function ($row, $index) use ($qualifications) {
            $rank = $index + 1;
            $rule = $qualifications->first(fn ($r) => $rank >= $r['rank_from'] && $rank <= $r['rank_to']);

            // A placement-only rule (destination_type=placement) isn't a "destination" to show
            // here — it's the final result itself, already reflected in place/points/cash_prize.
            $isAdvancement = $rule && ($rule['destination_type'] ?? null) === 'phase';

            return [
                'place' => $rank,
                'team' => $row['team'],
                'points' => $rule['points'] ?? null,
                'cash_prize' => $rule['cash_prize'] ?? null,
                'destination_label' => $isAdvancement ? $rule['label'] : null,
                'destination_url' => $isAdvancement ? $rule['url'] : null,
                'has_rule' => (bool) $rule,
            ];
        })
            // Only ranks that actually earn something (points, cash, or advancement) belong on
            // the leaderboard — the rest are already visible in the standings table above it.
            ->filter(fn ($row) => $row['has_rule'] && ($row['points'] || $row['cash_prize'] || $row['destination_label']))
            ->values();
    } elseif ($format === 'bracket') {
        $rows = collect($phase['matches'] ?? [])
            ->flatMap(fn ($match) => collect($match['qualifications'] ?? [])
                ->filter(fn ($q) => $q['destination_type'] === 'placement' && $q['placement'])
                ->map(fn ($q) => ['rule' => $q, 'match' => $match]))
            ->sortBy('rule.placement')
            ->values()
            ->map(function ($entry) {
                $q = $entry['rule'];
                $match = $entry['match'];

                $winnerIsA = ! is_null($match['team_a_score']) && ! is_null($match['team_b_score']) && $match['team_a_score'] > $match['team_b_score'];
                $winnerIsB = ! is_null($match['team_a_score']) && ! is_null($match['team_b_score']) && $match['team_b_score'] > $match['team_a_score'];
                $qualifiedIsA = ($q['outcome'] === 'winner' && $winnerIsA) || ($q['outcome'] === 'loser' && $winnerIsB);
                $qualifiedIsB = ($q['outcome'] === 'winner' && $winnerIsB) || ($q['outcome'] === 'loser' && $winnerIsA);

                return [
                    'place' => $q['placement'],
                    'team' => [
                        'id' => $qualifiedIsA ? ($match['team_a_id'] ?? null) : ($qualifiedIsB ? ($match['team_b_id'] ?? null) : null),
                        'name' => $qualifiedIsA ? ($match['team_a_name'] ?? null) : ($qualifiedIsB ? ($match['team_b_name'] ?? null) : null),
                        'logo' => $qualifiedIsA ? ($match['team_a_logo'] ?? null) : ($qualifiedIsB ? ($match['team_b_logo'] ?? null) : null),
                    ],
                    'points' => $q['points'],
                    'cash_prize' => $q['cash_prize'],
                    'destination_label' => null,
                    'destination_url' => null,
                ];
            })
            ->filter(fn ($row) => $row['team']['name']);
    }
@endphp

@php
    // Top 3 stand out individually (gold/silver/bronze); the rest read as one
    // connected list instead of each being its own separately-badged podium spot.
    $podiumAccents = [
        1 => ['badge' => 'bg-yellow-400/15 text-yellow-300', 'row' => 'from-yellow-400/[0.08] border-yellow-400/25'],
        2 => ['badge' => 'bg-slate-300/15 text-slate-200', 'row' => 'from-slate-300/[0.08] border-slate-300/25'],
        3 => ['badge' => 'bg-orange-600/20 text-orange-400', 'row' => 'from-orange-600/[0.08] border-orange-600/25'],
    ];

    // Every row reserves the same width for the destination column — sized to the
    // longest label in this leaderboard — so points/cash prize line up across rows
    // whether or not that row has a destination to show.
    $destinationColumnWidth = $rows->map(fn ($row) => mb_strlen($row['destination_label'] ?? ''))->max() ?: 0;
@endphp

@if ($rows->isNotEmpty())
    <div class="mt-4">
        <div class="flex items-center gap-2 mb-4">
            @svg('fas-trophy', 'w-3 h-3 text-gc-yellow shrink-0')
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('tournament.leaderboard.title') }}</span>
            <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
        </div>

        {{-- Deliberately not a <table> like the standings above it — a stack of pill rows
             reads as a distinct "results" widget instead of a second standings table. --}}
        <div class="space-y-1.5 {{ $rows->count() > 3 ? 'mb-1.5' : '' }}">
            @foreach ($rows->take(3) as $row)
                @php $accent = $podiumAccents[$row['place']] ?? $podiumAccents[3]; @endphp
                <div class="flex items-center gap-3 md:gap-4 bg-gradient-to-r {{ $accent['row'] }} to-transparent border rounded-full pl-1.5 pr-3 md:pr-5 py-1.5">
                    <span class="flex h-7 w-7 md:h-8 md:w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-black {{ $accent['badge'] }}">
                        {{ $row['place'] }}
                    </span>

                    <div class="flex-1 min-w-0">
                        @if ($row['team']['id'] ?? null)
                            <a href="{{ route('teams.show', [$row['team']['id'], str($row['team']['name'] ?? '')->slug()]) }}" class="flex items-center gap-2 group">
                                <img src="{{ $row['team']['logo'] ?? asset('storage/images/default-team.webp') }}"
                                     class="w-5 h-5 object-contain flex-shrink-0 transition-transform group-hover:scale-110" alt="">
                                <span class="text-[11px] md:text-xs font-bold uppercase text-white truncate group-hover:text-gc-yellow transition-colors">{{ $row['team']['name'] }}</span>
                            </a>
                        @else
                            <span class="text-[11px] md:text-xs font-bold uppercase text-white truncate">{{ $row['team']['name'] ?? 'Unknown' }}</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 md:gap-6 shrink-0">
                        <div class="text-center w-9 shrink-0">
                            <div class="text-[11px] md:text-xs font-black text-white truncate">{{ $row['points'] ?? '—' }}</div>
                            <div class="text-[7px] md:text-[8px] font-bold uppercase tracking-widest text-gray-500">{{ __('tournament.leaderboard.points') }}</div>
                        </div>
                        <div class="text-center w-16 shrink-0">
                            <div class="text-[11px] md:text-xs font-black text-white truncate">{{ $row['cash_prize'] ?? '—' }}</div>
                            <div class="text-[7px] md:text-[8px] font-bold uppercase tracking-widest text-gray-500">{{ __('tournament.leaderboard.cash_prize') }}</div>
                        </div>
                        <div class="text-right shrink-0" style="min-width: {{ $destinationColumnWidth }}ch">
                            @if ($row['destination_url'] ?? null)
                                <a href="{{ $row['destination_url'] }}" class="block whitespace-nowrap text-[10px] md:text-[11px] font-bold uppercase text-gc-yellow hover:underline">{{ $row['destination_label'] }}</a>
                            @elseif ($row['destination_label'] ?? null)
                                <span class="block whitespace-nowrap text-[10px] md:text-[11px] font-bold uppercase text-gray-400">{{ $row['destination_label'] }}</span>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($rows->count() > 3)
            {{-- The rest of the field, joined into one block (divide-y, no per-row rounding/
                 gradient) so it reads as a single ranked list rather than N individual cards. --}}
            <div class="border border-white/10 rounded-lg divide-y divide-white/5 overflow-hidden">
                @foreach ($rows->slice(3) as $row)
                    <div class="flex items-center gap-3 md:gap-4 px-3 md:px-5 py-2 hover:bg-white/[0.02] transition-colors">
                        <span class="flex h-7 w-7 md:h-8 md:w-8 shrink-0 items-center justify-center rounded-full bg-white/5 text-[11px] font-black text-gray-400">
                            {{ $row['place'] }}
                        </span>

                        <div class="flex-1 min-w-0">
                            @if ($row['team']['id'] ?? null)
                                <a href="{{ route('teams.show', [$row['team']['id'], str($row['team']['name'] ?? '')->slug()]) }}" class="flex items-center gap-2 group">
                                    <img src="{{ $row['team']['logo'] ?? asset('storage/images/default-team.webp') }}"
                                         class="w-5 h-5 object-contain flex-shrink-0 transition-transform group-hover:scale-110" alt="">
                                    <span class="text-[11px] md:text-xs font-bold uppercase text-gray-300 truncate group-hover:text-gc-yellow transition-colors">{{ $row['team']['name'] }}</span>
                                </a>
                            @else
                                <span class="text-[11px] md:text-xs font-bold uppercase text-gray-300 truncate">{{ $row['team']['name'] ?? 'Unknown' }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 md:gap-6 shrink-0">
                            <div class="text-center w-9 shrink-0">
                                <div class="text-[11px] md:text-xs font-black text-gray-300 truncate">{{ $row['points'] ?? '—' }}</div>
                                <div class="text-[7px] md:text-[8px] font-bold uppercase tracking-widest text-gray-600">{{ __('tournament.leaderboard.points') }}</div>
                            </div>
                            <div class="text-center w-16 shrink-0">
                                <div class="text-[11px] md:text-xs font-black text-gray-300 truncate">{{ $row['cash_prize'] ?? '—' }}</div>
                                <div class="text-[7px] md:text-[8px] font-bold uppercase tracking-widest text-gray-600">{{ __('tournament.leaderboard.cash_prize') }}</div>
                            </div>
                            <div class="text-right shrink-0" style="min-width: {{ $destinationColumnWidth }}ch">
                                @if ($row['destination_url'] ?? null)
                                    <a href="{{ $row['destination_url'] }}" class="block whitespace-nowrap text-[10px] md:text-[11px] font-bold uppercase text-gc-yellow hover:underline">{{ $row['destination_label'] }}</a>
                                @elseif ($row['destination_label'] ?? null)
                                    <span class="block whitespace-nowrap text-[10px] md:text-[11px] font-bold uppercase text-gray-500">{{ $row['destination_label'] }}</span>
                                @else
                                    <span class="text-gray-600">—</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
