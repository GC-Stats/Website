{{--
    GC-Stats — Head-to-head team picker

    Form to pick the two teams compared by the Face to Face widget. Submits
    as a plain GET so the result is a shareable/bookmarkable URL, consistent
    with the phase/date filters already used on tournament stats & maps
    pages.

    On the tournament maps page ($tournamentTeams is set), both teams use the
    generic entity-picker with its browse list scoped to that tournament's
    participants (search still reaches any team site-wide, same as
    elsewhere) — no date range (the comparison always covers the whole
    tournament). On team pages, team A is locked to the current team and
    team B is picked via the entity-picker (any team, site-wide), plus an
    optional date range.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['action', 'preserve' => [], 'lockTeamA' => null, 'tournamentTeams' => null])

<div class="bg-black/40 backdrop-blur-md border border-white/10 rounded-xl p-4 shadow-2xl space-y-3">
    <span class="text-[9px] uppercase font-black text-gray-500 tracking-[0.2em]">
        {{ __('head_to_head.title') }}
    </span>

    <form method="GET" action="{{ $action }}" class="space-y-3">
        @foreach($preserve as $key)
            @if(request()->filled($key))
                <input type="hidden" name="{{ $key }}" value="{{ request()->query($key) }}">
            @endif
        @endforeach

        @if($tournamentTeams !== null)
            <livewire:entity-picker type="team" name="h2h_team_a" :label="__('head_to_head.picker.team_a')" :selected="request()->query('h2h_team_a')" :browseIds="$tournamentTeams->pluck('id')->all()" />

            <livewire:entity-picker type="team" name="h2h_team_b" :label="__('head_to_head.picker.team_b')" :selected="request()->query('h2h_team_b')" :browseIds="$tournamentTeams->pluck('id')->all()" />
        @else
            @if(! $lockTeamA)
                <livewire:entity-picker type="team" name="h2h_team_a" :label="__('head_to_head.picker.team_a')" :selected="request()->query('h2h_team_a')" />
            @endif

            <livewire:entity-picker type="team" name="h2h_team_b" :label="__('head_to_head.picker.team_b')" :selected="request()->query('h2h_team_b')" />

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('head_to_head.picker.start_date') }}</label>
                    <input type="date" name="h2h_start_date" value="{{ request()->query('h2h_start_date') }}"
                           class="w-full py-2 px-3 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                </div>
                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('head_to_head.picker.end_date') }}</label>
                    <input type="date" name="h2h_end_date" value="{{ request()->query('h2h_end_date') }}"
                           class="w-full py-2 px-3 text-xs rounded-sm bg-[#050505] border border-border-subtle text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                </div>
            </div>
        @endif

        <div class="flex items-center gap-2">
            <button type="submit" class="flex-1 py-2 text-[10px] font-black uppercase tracking-wider rounded-md bg-[var(--brand-yellow)] text-black hover:opacity-90 transition-opacity">
                {{ __('head_to_head.picker.compare') }}
            </button>

            @if(request()->filled('h2h_team_b'))
                <a href="{{ request()->fullUrlWithQuery(['h2h_team_a' => null, 'h2h_team_b' => null, 'h2h_start_date' => null, 'h2h_end_date' => null]) }}"
                   class="px-4 py-2 text-[10px] font-black uppercase tracking-wider rounded-md text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                    {{ __('head_to_head.picker.reset') }}
                </a>
            @endif
        </div>
    </form>
</div>
