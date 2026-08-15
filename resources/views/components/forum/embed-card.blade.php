{{--
    GC-Stats — Forum embed card

    Renders one `{{type:id}}` / `{{type:id:variant}}` reference from
    ForumMessage::parseBody() — the replacement for pasting a screenshot.
    $type is 'player'|'team'|'match', $model the already-loaded model
    (ForumMessage::resolveEmbed() drops any reference whose model no
    longer exists), $variant is 'header'|'stats' (ignored for match, which
    has only one visual), $stats the pre-computed summary for the "stats"
    variant (ForumMessage::resolveEmbedStats()).

    Deliberately NOT the literal page components (x-match.score-header,
    x-player/team.identity-card) — those are built for a full page's width
    and lean on Tailwind's `md:` breakpoints, which key off the *browser
    viewport*, not this card's own (much narrower) width. Reusing them
    verbatim inside a ~24-28rem embed meant the "desktop" responsive layout
    kicked in on a normal desktop while the actual box stayed phone-width,
    breaking the layout. This card matches the same color/type language
    (dark card, gc-yellow accents, same font weights) with fixed, non-
    responsive sizing built for its own width instead.

    Match data still comes from Matchs::toScoreHeaderArray() — the same
    time-aware team name/logo resolution the real match page uses, just
    laid out compactly here.

    Every card root gets `whitespace-normal`: the message wrapper these
    cards sit inside (see forum-thread.blade.php) sets
    `white-space: pre-line` so line breaks the user actually typed show up
    — inherited into a card, that turns the newlines Blade's own
    `@if/@else` directives leave in the compiled HTML (invisible in
    ordinary flow) into real forced line breaks, inflating a single-line
    label into a 2-3 line one and throwing off the centered alignment with
    its siblings. Resetting to `normal` here (collapse whitespace, no
    forced breaks) is what a card actually wants regardless of the
    message's own formatting.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['type', 'model', 'variant' => 'header', 'stats' => null, 'filters' => null, 'matchData' => null])

@php
    // "Jett · Last 30 days · VCT Game Changers" — blank when no filter of
    // that kind was picked, so an unfiltered stats card shows no subtitle
    // at all rather than an empty " · · " trail.
    $filterLine = collect($filters ?? [])->filter()->implode(' · ');
@endphp

@if ($type === 'match' && $variant === 'player')
    @php $line = $matchData['player']; @endphp
    <a href="{{ route('match.show', $model->id) }}"
       class="not-prose whitespace-normal block max-w-xs my-1 bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden hover:border-white/20 transition">
        <div class="flex items-center gap-2 p-3 pb-0">
            <span class="text-sm font-black text-white truncate">{{ $line['player_handle'] }}</span>
            <span class="text-[9px] font-bold uppercase tracking-widest text-gray-500 truncate">{{ $model->teamA?->name }} {{ __('forum.embed.vs') }} {{ $model->teamB?->name }}</span>
        </div>
        <div class="grid grid-cols-3 gap-px bg-white/5 mt-3">
            @foreach ([
                ['label' => __('forum.embed.stats.acs'), 'value' => $line['acs']],
                ['label' => 'K/D/A', 'value' => $line['kills'].'/'.$line['deaths'].'/'.$line['assists']],
                ['label' => __('forum.embed.stats.adr'), 'value' => $line['adr']],
                ['label' => __('forum.embed.stats.kast'), 'value' => $line['kast_percentage'].'%'],
                ['label' => __('forum.embed.stats.hs'), 'value' => $line['headshot_percentage'].'%'],
                ['label' => __('forum.embed.stats.games'), 'value' => $line['maps_played']],
            ] as $tile)
                <div class="bg-bg-main p-2 text-center">
                    <p class="text-sm font-black text-white">{{ $tile['value'] }}</p>
                    <p class="text-[8px] font-bold uppercase tracking-widest text-gray-500">{{ $tile['label'] }}</p>
                </div>
            @endforeach
        </div>
    </a>
@elseif ($type === 'match' && $variant === 'scoreboard')
    <a href="{{ route('match.show', $model->id) }}"
       class="not-prose whitespace-normal block max-w-lg my-1 bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden hover:border-white/20 transition">
        {{-- Keyed by 'stats_a'/'stats_b' (App\Services\MatchStatsService::aggregateFor()'s naming), not 'team_a'/'team_b' --}}
        @foreach (['stats_a' => $model->teamA, 'stats_b' => $model->teamB] as $key => $team)
            <div class="px-3 py-2 border-b border-white/5 last:border-b-0">
                <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ $team?->name ?? __('match.team_tbd') }}</p>
                @if (count($matchData[$key]) > 0)
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="text-[7px] font-bold uppercase tracking-widest text-gray-600">
                                <th class="text-left font-bold pb-1">{{ __('forum.embed.stats.player') }}</th>
                                <th class="text-right font-bold pb-1 pl-2">{{ __('forum.embed.stats.acs') }}</th>
                                <th class="text-right font-bold pb-1 pl-2">{{ __('forum.embed.stats.kda') }}</th>
                                <th class="text-right font-bold pb-1 pl-2">{{ __('forum.embed.stats.adr') }}</th>
                                <th class="text-right font-bold pb-1 pl-2">{{ __('forum.embed.stats.kast') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($matchData[$key] as $line)
                                <tr class="text-[11px] border-t border-white/5">
                                    <td class="py-1 text-white font-bold truncate max-w-[7rem]">{{ $line['player_handle'] }}</td>
                                    <td class="py-1 pl-2 text-right text-gray-300 font-bold">{{ $line['acs'] }}</td>
                                    <td class="py-1 pl-2 text-right text-gray-400 whitespace-nowrap">{{ $line['kills'] }}/{{ $line['deaths'] }}/{{ $line['assists'] }}</td>
                                    <td class="py-1 pl-2 text-right text-gray-400">{{ $line['adr'] }}</td>
                                    <td class="py-1 pl-2 text-right text-gray-400">{{ $line['kast_percentage'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-[11px] text-gray-600 italic">—</p>
                @endif
            </div>
        @endforeach
    </a>
@elseif ($type === 'match' && $variant === 'performance')
    <a href="{{ route('match.show', $model->id) }}"
       class="not-prose whitespace-normal block max-w-md my-1 bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden hover:border-white/20 transition">
        @foreach (['stats_a' => $model->teamA, 'stats_b' => $model->teamB] as $key => $team)
            <div class="px-3 py-2 border-b border-white/5 last:border-b-0">
                <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ $team?->name ?? __('match.team_tbd') }}</p>
                <div class="space-y-1">
                    @forelse ($matchData[$key] as $line)
                        @php $perf = $matchData['performance'][$line['player_id']] ?? ['2k' => 0, '3k' => 0, '4k' => 0, '5k' => 0, 'sheriff_kills' => 0]; @endphp
                        <div class="flex items-center justify-between gap-2 text-[11px]">
                            <span class="text-white font-bold truncate">{{ $line['player_handle'] }}</span>
                            <span class="text-gray-400 shrink-0">2K {{ $perf['2k'] }} &middot; 3K {{ $perf['3k'] }} &middot; 4K {{ $perf['4k'] }} &middot; 5K {{ $perf['5k'] }}</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-600 italic">—</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </a>
@elseif ($type === 'match' && $variant === 'economy')
    <a href="{{ route('match.show', $model->id) }}"
       class="not-prose whitespace-normal block max-w-md my-1 bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden hover:border-white/20 transition">
        @foreach (['team_a' => $model->teamA, 'team_b' => $model->teamB] as $key => $team)
            <div class="px-3 py-2 border-b border-white/5 last:border-b-0">
                <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ $team?->name ?? __('match.team_tbd') }}</p>
                <div class="grid grid-cols-4 gap-px bg-white/5">
                    @foreach ($matchData['eco_summary'][$key] as $tier)
                        <div class="bg-bg-main p-1.5 text-center">
                            <p class="text-xs font-black text-white">{{ $tier['win'] }}/{{ $tier['total'] }}</p>
                            <p class="text-[7px] font-bold uppercase tracking-widest text-gray-500 truncate">{{ $tier['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </a>
@elseif ($type === 'match')
    @php $match = $model->toScoreHeaderArray(); @endphp
    <a href="{{ route('match.show', $match['id']) }}"
       class="not-prose whitespace-normal block max-w-md my-1 bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden hover:border-white/20 transition">
        <div class="flex items-center justify-between gap-2 px-3 pt-2.5 text-[9px] font-black uppercase tracking-widest text-gray-500">
            <span class="truncate">{{ $match['tournament']['name'] ?? __('match.unknown_tournament') }}</span>
            <span class="shrink-0 {{ $match['status'] === 'live' ? 'text-red-400' : '' }}">
                {{ __('forum.embed.match_status.'.$match['status']) }}
            </span>
        </div>
        <div class="flex items-center gap-2 px-3 py-3">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <img src="{{ $match['team_a_data']['logo'] ?? asset('storage/images/default-team.webp') }}" alt="" class="w-7 h-7 object-contain shrink-0">
                <span class="text-xs font-bold text-white truncate">{{ $match['team_a_data']['name'] ?? __('match.team_tbd') }}</span>
            </div>
            <span class="text-base font-black text-white shrink-0 px-2 tracking-tighter">
                @if (in_array($match['status'], ['finished', 'live'], true))
                    {{ $match['team_a_score'] == -1 ? 'FF' : $match['team_a_score'] }}&nbsp;–&nbsp;{{ $match['team_b_score'] == -1 ? 'FF' : $match['team_b_score'] }}
                @else
                    {{ __('forum.embed.vs') }}
                @endif
            </span>
            <div class="flex items-center gap-2 min-w-0 flex-1 justify-end">
                <span class="text-xs font-bold text-white truncate">{{ $match['team_b_data']['name'] ?? __('match.team_tbd') }}</span>
                <img src="{{ $match['team_b_data']['logo'] ?? asset('storage/images/default-team.webp') }}" alt="" class="w-7 h-7 object-contain shrink-0">
            </div>
        </div>
    </a>
@elseif ($type === 'player')
    <div class="not-prose whitespace-normal block max-w-xs my-1 bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden">
        @if ($variant === 'stats')
            <a href="{{ route('players.show', [$model->id, \Illuminate\Support\Str::routeSlug($model->handle, $model->id)]) }}"
               class="flex items-center gap-2 p-3 pb-0 hover:opacity-80 transition">
                <img src="{{ $model->profile_photo ?: asset('storage/images/default-player.webp') }}" alt="" class="w-6 h-6 rounded-full object-cover shrink-0">
                <div class="min-w-0">
                    <span class="block text-sm font-black text-white truncate">{{ $model->handle }}</span>
                    @if ($filterLine)
                        <span class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 truncate">{{ $filterLine }}</span>
                    @endif
                </div>
            </a>
            <div class="grid grid-cols-3 gap-px bg-white/5 mt-3">
                @foreach ([
                    ['label' => __('forum.embed.stats.games'), 'value' => $stats['games_played'] ?? 0],
                    ['label' => __('forum.embed.stats.acs'), 'value' => $stats['avg_acs'] ?? '—'],
                    ['label' => __('forum.embed.stats.kd'), 'value' => ($stats['avg_deaths'] ?? 0) > 0 ? round(($stats['avg_kills'] ?? 0) / $stats['avg_deaths'], 2) : ($stats['avg_kills'] ?? '—')],
                    ['label' => __('forum.embed.stats.adr'), 'value' => $stats['avg_adr'] ?? '—'],
                    ['label' => __('forum.embed.stats.kast'), 'value' => isset($stats['avg_kast']) ? $stats['avg_kast'].'%' : '—'],
                    ['label' => __('forum.embed.stats.hs'), 'value' => isset($stats['avg_hs']) ? $stats['avg_hs'].'%' : '—'],
                ] as $tile)
                    <div class="bg-bg-main p-2 text-center">
                        <p class="text-sm font-black text-white">{{ $tile['value'] }}</p>
                        <p class="text-[8px] font-bold uppercase tracking-widest text-gray-500">{{ $tile['label'] }}</p>
                    </div>
                @endforeach
            </div>
        @else
            @php $currentTeam = $model->teams->whereNull('pivot.left_at')->first(); @endphp
            <a href="{{ route('players.show', [$model->id, \Illuminate\Support\Str::routeSlug($model->handle, $model->id)]) }}"
               class="flex items-center gap-3 p-3 hover:bg-white/[0.03] transition">
                <img src="{{ $model->profile_photo ?: asset('storage/images/default-player.webp') }}" alt="{{ $model->handle }}"
                     class="w-10 h-10 rounded-full object-cover border border-white/10 shrink-0">
                <div class="min-w-0">
                    <p class="text-sm font-black text-white truncate">{{ $model->handle }}</p>
                    @if ($currentTeam)
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 truncate">{{ $currentTeam->name }}</p>
                    @endif
                </div>
            </a>
        @endif
    </div>
@elseif ($type === 'team')
    <div class="not-prose whitespace-normal block max-w-xs my-1 bg-white/[0.03] border border-white/[0.08] rounded-xl overflow-hidden">
        @if ($variant === 'stats')
            <a href="{{ route('teams.show', [$model->id, $model->routeSlug()]) }}"
               class="flex items-center gap-2 p-3 pb-0 hover:opacity-80 transition">
                <img src="{{ $model->logo }}" alt="" class="w-6 h-6 object-contain shrink-0">
                <div class="min-w-0">
                    <span class="block text-sm font-black text-white truncate">{{ $model->name }}</span>
                    @if ($filterLine)
                        <span class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 truncate">{{ $filterLine }}</span>
                    @endif
                </div>
            </a>
            <div class="grid grid-cols-4 gap-px bg-white/5 mt-3">
                @foreach ([
                    ['label' => __('forum.embed.stats.matches'), 'value' => $stats['matches_played'] ?? 0],
                    ['label' => __('forum.embed.stats.wins'), 'value' => $stats['wins'] ?? 0],
                    ['label' => __('forum.embed.stats.losses'), 'value' => $stats['losses'] ?? 0],
                    ['label' => __('forum.embed.stats.win_rate'), 'value' => isset($stats['win_rate']) ? $stats['win_rate'].'%' : '—'],
                ] as $tile)
                    <div class="bg-bg-main p-2 text-center">
                        <p class="text-sm font-black text-white">{{ $tile['value'] }}</p>
                        <p class="text-[8px] font-bold uppercase tracking-widest text-gray-500">{{ $tile['label'] }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <a href="{{ route('teams.show', [$model->id, $model->routeSlug()]) }}"
               class="flex items-center gap-3 p-3 hover:bg-white/[0.03] transition">
                <img src="{{ $model->logo }}" alt="{{ $model->name }}" class="w-10 h-10 object-contain shrink-0">
                <div class="min-w-0">
                    <p class="text-sm font-black text-white truncate">{{ $model->name }}</p>
                    @if ($model->short_name)
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 truncate">{{ $model->short_name }}</p>
                    @endif
                </div>
            </a>
        @endif
    </div>
@endif
