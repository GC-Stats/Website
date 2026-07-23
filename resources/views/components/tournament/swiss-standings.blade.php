{{--
    GC-Stats — Swiss-stage standings component

    Computes and renders the standings table for a Swiss-format tournament
    phase (wins/losses, map differential, opponent strength).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['matches' => [], 'phase' => null, 'teams' => [], 'showBuchholz' => false])

@php
    $standings = \App\Support\TournamentStandings::compute($matches, $teams, $showBuchholz);

    $advancementRules = collect($phase['qualifications'] ?? [])->where('destination_type', 'phase')->values();
    $hasQualificationRules = $advancementRules->isNotEmpty();
@endphp

<div class="border border-border-subtle rounded-sm w-full bg-bg-card overflow-hidden">
    <table class="w-full text-left border-collapse table-auto">
        <thead class="bg-white/5 text-[8px] md:text-[10px] uppercase font-black text-gray-500 tracking-widest">
        <tr>
            <th class="p-2 md:p-4 border-b border-border-subtle w-8 md:w-16 text-center">#</th>
            <th class="p-2 md:p-4 border-b border-border-subtle">{{ __("tournament.swiss_stage.team") }}</th>
            <th class="p-2 md:p-4 border-b border-border-subtle text-center w-16 md:w-48">{{ __("tournament.swiss_stage.matches") }}</th>
            <th class="p-2 md:p-4 border-b border-border-subtle text-center w-16 md:w-48">{{ __("tournament.swiss_stage.maps") }}</th>
            @if($showBuchholz)
                <th class="p-2 md:p-4 border-b border-border-subtle text-center w-16 md:w-32">{{ __("tournament.swiss_stage.buchholz") }}</th>
            @endif
            <th class="p-2 md:p-4 border-b border-border-subtle text-right w-24 md:w-64">{{ __("tournament.swiss_stage.rounds") }}</th>
        </tr>
        </thead>
        <tbody class="text-[10px] md:text-[12px] font-bold uppercase italic">
        @foreach($standings as $index => $row)
            @php
                $rank = $index + 1;
                $qualificationRule = $advancementRules
                    ->first(fn($r) => $rank >= $r['rank_from'] && $rank <= $r['rank_to']);
            @endphp
            <tr class="border-b border-white/5 last:border-b-0 hover:bg-white/[0.02] transition-colors border-l-2 {{ ! $hasQualificationRules ? 'border-l-transparent' : ($qualificationRule ? 'border-l-green-500/60' : 'border-l-red-500/60') }}">
                <td class="p-2 md:p-4 text-gray-600 text-center font-mono">{{ $index + 1 }}</td>

                <td class="p-2 md:p-4">
                    <a href="{{ route('teams.show', [$row['team']['id'], str($row['team']['name'] ?? '')->slug()]) }}" class="flex items-center gap-3 group">
                        <img src="{{ $row['team']['logo'] ?? asset('storage/images/default-team.webp') }}"
                             class="w-5 h-5 md:w-6 md:h-6 object-contain flex-shrink-0 transition-transform group-hover:scale-110"
                             alt="">
                        <span class="text-white truncate max-w-[120px] md:max-w-none group-hover:text-gc-yellow transition-colors">
                                {{ $row['team']['name'] ?? 'Unknown' }}
                            </span>
                    </a>
                </td>

                <td class="p-2 md:p-4 text-center font-mono text-white">
                    <div class="inline-flex items-center justify-center px-2 py-1 min-w-[40px] md:min-w-[80px]">
                        {{ $row['wins'] }} - {{ $row['losses'] }}
                    </div>
                </td>

                <td class="p-2 md:p-4 text-center font-mono text-white">
                    <div class="inline-flex items-center justify-center px-2 py-1 min-w-[40px] md:min-w-[80px]">
                        {{ $row['map_wins'] }} - {{ $row['map_losses'] }}
                    </div>
                </td>

                @if($showBuchholz)
                    <td class="p-2 md:p-4 text-center font-mono text-white">
                        {{ $row['buchholz'] }}
                    </td>
                @endif

                <td class="p-2 md:p-4 text-right font-mono">
                    <div class="flex flex-col md:flex-row items-end md:items-center justify-end md:gap-4">
                            <span class="{{ $row['round_diff'] > 0 ? 'text-green-500' : ($row['round_diff'] < 0 ? 'text-red-500' : 'text-gray-500') }} text-[11px] md:text-[14px]">
                                {{ $row['round_diff'] > 0 ? '+' : '' }}{{ $row['round_diff'] }}
                            </span>
                        <span class="text-[8px] md:text-[10px] text-gray-600 font-normal not-italic opacity-80">
                                {{ $row['round_wins'] }}W / {{ $row['round_losses'] }}L
                            </span>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <x-tournament.qualification-legend :qualifications="$advancementRules" />
</div>
