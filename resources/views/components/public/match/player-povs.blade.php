{{--
    GC-Stats — Match Player POV section

    Lists the Twitch channels auto-detected as official Player POVs for this
    match (see App\Console\Commands\ScheduledCommand\DetectPlayerPovStreams
    and App\Models\MatchPlayerPov) — a player's own channel, or a team's
    channel (player null). $match['player_povs'] is the cached match
    payload's array, each entry carrying title, url, player (nullable
    id/handle) and team (id/name/short_name).

    Unlike streams/vods, this section renders nothing at all when there is
    nothing detected — there's no "add a POV" admin action, so an empty
    state would just be noise on every match page.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php $playerPovs = $match['player_povs'] ?? []; @endphp

@if (! empty($playerPovs))
    <div class="mt-10 -mx-4 md:-mx-8">
        <div class="flex items-center justify-center gap-4 mb-8">
            <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent to-white/10"></div>
            <span class="flex items-center gap-1.5 text-[8px] font-black text-gray-600 uppercase tracking-[0.5em]">
                {{ __('match.player_povs.title') }}
                <span class="group relative inline-flex" tabindex="0">
                    @svg('fas-circle-info', 'w-3 h-3 text-gray-600 hover:text-gray-400 transition-colors cursor-help', ['aria-hidden' => 'true'])
                    <span class="sr-only">{{ __('match.player_povs.info') }}</span>
                    <span role="tooltip"
                          class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 normal-case tracking-normal font-normal text-[11px] text-gray-300 bg-[#0a0a0a] border border-border-subtle rounded-sm px-3 py-2 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity z-10">
                        {{ __('match.player_povs.info') }}
                    </span>
                </span>
            </span>
            <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent to-white/10"></div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 px-6">
            @foreach ($playerPovs as $pov)
                <a href="{{ $pov['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="group flex items-center gap-2 px-4 py-2 rounded-full transition active:scale-95 bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:border-white/20">
                    @svg('fab-twitch', 'w-3.5 h-3.5 flex-shrink-0 text-gray-400 group-hover:text-[var(--brand-yellow)] transition-colors', ['aria-hidden' => 'true'])
                    <span class="text-xs font-bold truncate max-w-[160px]">
                        {{ $pov['player']['handle'] ?? $pov['team']['short_name'] ?? $pov['team']['name'] ?? $pov['twitch_login'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
