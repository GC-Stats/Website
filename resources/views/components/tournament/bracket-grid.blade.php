{{--
    GC-Stats — Tournament bracket grid component

    Renders an elimination bracket as a horizontally scrollable grid of
    rounds, connecting matches across rounds.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['matches' => []])

<div class="inline-flex gap-0 pb-4 will-change-transform">
    @php
        $rounds = collect($matches)->groupBy('round_number')->sortKeys();
        $roundKeys = $rounds->keys()->values()->toArray();
        $roundsArray = $rounds
            ->map(fn($roundMatches) => collect($roundMatches)->sortBy('match_order')->values())
            ->values()
            ->toArray();

        $lastRoundMatches = $roundsArray[count($roundsArray) - 1] ?? collect();
        $qualifierSlots = collect($lastRoundMatches)->flatMap(function ($match) {
            $winnerIsA = ! is_null($match['team_a_score']) && ! is_null($match['team_b_score']) && $match['team_a_score'] > $match['team_b_score'];
            $winnerIsB = ! is_null($match['team_a_score']) && ! is_null($match['team_b_score']) && $match['team_b_score'] > $match['team_a_score'];

            $qualifications = collect($match['qualifications'] ?? [])->filter(fn ($q) => $q['destination_type'] === 'phase');

            return $qualifications->map(function ($qualification) use ($match, $winnerIsA, $winnerIsB) {
                $qualifiedIsA = ($qualification['outcome'] === 'winner' && $winnerIsA) || ($qualification['outcome'] === 'loser' && $winnerIsB);
                $qualifiedIsB = ($qualification['outcome'] === 'winner' && $winnerIsB) || ($qualification['outcome'] === 'loser' && $winnerIsA);
                $qualifiedName = $qualifiedIsA ? ($match['team_a_name'] ?? null) : ($qualifiedIsB ? ($match['team_b_name'] ?? null) : null);

                if (! $qualifiedName) {
                    return null;
                }

                return [
                    'name' => $qualifiedName,
                    'logo' => $qualifiedIsA ? ($match['team_a_logo'] ?? null) : ($match['team_b_logo'] ?? null),
                    'url' => $qualification['url'] ?? '#',
                    'label' => $qualification['label'],
                    'source_match_id' => $match['id'],
                ];
            });
        })->filter()->values();

        if ($qualifierSlots->isNotEmpty()) {
            $roundsArray[] = $qualifierSlots;
            $roundKeys[] = 'qualifiers';
        }

        $totalRounds = count($roundsArray);
    @endphp

    @foreach($roundsArray as $roundIndex => $matches)
        @php
            $isLast = $roundIndex === $totalRounds - 1;
            $isQualifierRound = $roundKeys[$roundIndex] === 'qualifiers';
        @endphp

        <div class="flex items-stretch">
            <div class="flex flex-col py-4 min-w-[220px] px-4" @if($isQualifierRound) data-round-type="qualifiers" @endif>
                <div class="text-center mb-4">
                    <span class="text-[9px] font-black text-gc-yellow/80 uppercase tracking-widest">
                        {{ $isQualifierRound ? __('tournament.bracket.qualifiers_column') : (collect($matches)->first()['round_name'] ?? 'Round ' . $roundKeys[$roundIndex]) }}
                    </span>
                </div>

                <div class="flex-1 flex flex-col justify-around gap-2">
                    @if($isQualifierRound)
                        @foreach($matches as $slot)
                            <div class="bracket-match" data-source-match-id="{{ $slot['source_match_id'] }}">
                                <a href="{{ $slot['url'] }}"
                                   draggable="false"
                                   title="{{ __('tournament.bracket.qualified_tooltip', ['team' => $slot['name'], 'destination' => $slot['label']]) }}"
                                   class="block bg-bg-card border border-green-500/30 rounded-sm shadow-md overflow-hidden hover:border-green-500/60 transition-all group active:scale-[0.98]">
                                    <div class="flex items-center justify-between p-2">
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $slot['logo'] ?? asset('storage/images/default-team.webp') }}" class="w-4 h-4 object-contain flex-shrink-0 logo-filter" draggable="false">
                                            <span class="text-[10px] font-black italic uppercase text-white truncate">
                                                {{ Str::limit($slot['name'], 20) }}
                                            </span>
                                        </div>
                                        @svg('fas-flag-checkered', 'w-3 h-3 text-green-400 flex-shrink-0')
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    @else
                        @foreach($matches as $match)
                            <div class="bracket-match" data-match-id="{{ $match['id'] }}">
                                <a href="{{ route('match.show', $match['id']) }}"
                                   draggable="false"
                                   class="block bg-bg-card border border-border-subtle rounded-sm shadow-md overflow-hidden hover:border-gc-yellow/50 transition-all group active:scale-[0.98]">

                                    {{-- Team A --}}
                                    <div class="flex items-center justify-between p-2 border-b border-white/5 {{ !is_null($match['team_a_score']) && $match['team_a_score'] > $match['team_b_score'] ? 'bg-gc-yellow/5' : '' }}">
                                        <div class="flex items-center gap-2">
                                            @if(!empty($match['team_a_name']))
                                                <img src="{{ $match['team_a_logo'] ?? asset('storage/images/default-team.webp') }}" class="w-4 h-4 object-contain flex-shrink-0 logo-filter" draggable="false">
                                                <span class="text-[10px] font-black italic uppercase group-hover:text-white transition-colors {{ !is_null($match['team_a_score']) && $match['team_a_score'] > $match['team_b_score'] ? 'text-white' : 'text-gray-400' }}">
                                                    {{ Str::limit($match['team_a_name'], 20) }}
                                                </span>
                                            @else
                                                <span class="text-[10px] font-black italic uppercase text-gray-600">{{ $match["status"] == "finished" ? 'BYE' : ($match["status"] == "upcoming" ? 'TBD' : '-') }}</span>
                                            @endif
                                        </div>
                                        <span class="font-mono text-xs text-white">{{ $match['team_a_score'] == -1 ? 'FF' : ($match['team_a_score'] ?? '-') }}</span>
                                    </div>

                                    {{-- Team B --}}
                                    <div class="flex items-center justify-between p-2 {{ !is_null($match['team_b_score']) && $match['team_b_score'] > $match['team_a_score'] ? 'bg-gc-yellow/5' : '' }}">
                                        <div class="flex items-center gap-2">
                                            @if(!empty($match['team_b_name']))
                                                <img src="{{ $match['team_b_logo'] ?? asset('storage/images/default-team.webp') }}" class="w-4 h-4 object-contain flex-shrink-0 logo-filter" draggable="false">
                                                <span class="text-[10px] font-black italic uppercase group-hover:text-white transition-colors {{ !is_null($match['team_b_score']) && $match['team_b_score'] > $match['team_a_score'] ? 'text-white' : 'text-gray-400' }}">
                                                    {{ Str::limit($match['team_b_name'], 20) }}
                                                </span>
                                            @else
                                                <span class="text-[10px] font-black italic uppercase text-gray-600">{{ $match["status"] == "finished" ? 'BYE' : ($match["status"] == "upcoming" ? 'TBD' : '-') }}</span>
                                            @endif
                                        </div>
                                        <span class="font-mono text-xs text-white">{{ $match['team_b_score'] == -1 ? 'FF' : ($match['team_b_score'] ?? '-') }}</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            @if(!$isLast)
                <svg class="bracket-connector flex-shrink-0 self-stretch"
                     style="width: 32px; overflow: visible; min-height: 100%"
                     preserveAspectRatio="none">
                </svg>
            @endif
        </div>
    @endforeach
</div>
<script>
    function drawBracketConnectors() {
        document.querySelectorAll('.bracket-connector').forEach(svg => {
            svg.innerHTML = '';

            const parent     = svg.closest('.flex.items-stretch');
            const currentCol = parent.querySelector('.flex-col.py-4');
            const nextParent = parent.nextElementSibling;
            const nextCol    = nextParent?.querySelector('.flex-col.py-4');

            if (!currentCol || !nextCol) return;

            const currentMatches = currentCol.querySelectorAll('.bracket-match');
            const nextMatches    = nextCol.querySelectorAll('.bracket-match');

            if (!currentMatches.length || !nextMatches.length) return;

            const transformedEl = svg.closest('.inline-block');
            let scale = 1;
            if (transformedEl) {
                const matrix = new DOMMatrix(getComputedStyle(transformedEl).transform);
                if (matrix.a && matrix.a !== 0) scale = matrix.a;
            }

            const svgRect         = svg.getBoundingClientRect();
            const transformedRect = transformedEl ? transformedEl.getBoundingClientRect() : svgRect;

            const relY = (el) => {
                const r = el.getBoundingClientRect();
                return (r.top + r.height / 2 - transformedRect.top) / scale
                    - (svgRect.top - transformedRect.top) / scale;
            };

            if (nextCol.dataset.roundType === 'qualifiers') {
                nextMatches.forEach(target => {
                    const source = Array.from(currentMatches)
                        .find(m => m.dataset.matchId === target.dataset.sourceMatchId);
                    if (!source) return;

                    const yA = Math.round(relY(source));
                    const yT = Math.round(relY(target));

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', `M 0 ${yA} H 16 V ${yT} H 32`);
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke', 'rgba(255,255,255,0.35)');
                    path.setAttribute('stroke-width', '1');
                    path.setAttribute('stroke-linecap', 'round');
                    path.setAttribute('stroke-linejoin', 'round');
                    svg.appendChild(path);
                });
                return;
            }

            const isStraight = currentMatches.length === nextMatches.length;

            nextMatches.forEach((target, i) => {
                const matchA = currentMatches[isStraight ? i : i * 2];
                const matchB = !isStraight ? currentMatches[i * 2 + 1] : null;
                if (!matchA) return;

                const yA   = Math.round(relY(matchA));
                const yB   = matchB ? Math.round(relY(matchB)) : yA;
                const yT   = Math.round(relY(target));
                const yMid = Math.round((yA + yB) / 2);

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

                const d = matchB
                    ? `M 0 ${yA} H 16 V ${yB} M 0 ${yB} H 16 M 16 ${yMid} H 32 V ${yT}`
                    : `M 0 ${yA} H 16 V ${yT} H 32`;

                path.setAttribute('d', d);
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', 'rgba(255,255,255,0.35)');
                path.setAttribute('stroke-width', '1');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-linejoin', 'round');
                svg.appendChild(path);
            });
        });
    }

    const observer = new MutationObserver(() => {
        requestAnimationFrame(drawBracketConnectors);
    });

    observer.observe(document.body, {
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class'],
    });

    requestAnimationFrame(() => requestAnimationFrame(drawBracketConnectors));
</script>
