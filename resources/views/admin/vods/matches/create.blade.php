{{--
    GC-Stats — Admin: add a VOD (tournament -> match -> fields wizard)

    Lets a user reach any match (an active tournament's, or any tournament's
    if they hold vods.matches.link — see Admin\MatchVodController) without
    going through the admin match list, which publishers can't reach. Once
    a tournament is picked, every one of its matches is fetched once and
    rendered as a sortable table on the left — same columns as
    admin/matches/index.blade.php (phase, round, teams, status, date) —
    sorted/filtered client-side against that already-fetched list; the VOD
    fields sit to its right. Unlike the streams wizard, only one match can
    be picked here — a VOD row ties to exactly one match (and optionally
    one of its maps), so there's no batch cross-product to build. Submits
    to the same per-match route used by the admin match page and the
    public match page (Admin\MatchVodController::store()), built
    client-side since it depends on which match was picked. $countries is
    App\Support\Countries::list(), same source as the per-match VOD panel
    (admin/matches/_vods.blade.php).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.vods.matches.create_title'))

@section('content')
    <a href="{{ route('admin.vods.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.vods.matches.title') }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ __('admin.vods.matches.create_title') }}</h1>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl"
         x-data="{
            tournament: null, tournamentQuery: '', tournamentResults: [],
            matches: [], matchFilter: '', sortField: 'scheduled_at', sortDir: 'desc',
            match: null,
            async searchTournaments() {
                if (this.tournamentQuery.length < 2) { this.tournamentResults = []; return; }
                const res = await fetch(`{{ route('admin.vods.search-tournaments') }}?q=${encodeURIComponent(this.tournamentQuery)}`);
                this.tournamentResults = await res.json();
            },
            async pickTournament(t) {
                this.tournament = t; this.tournamentResults = []; this.tournamentQuery = t.label;
                this.match = null;
                const res = await fetch(`{{ route('admin.vods.search-matches') }}?tournament_id=${t.id}`);
                this.matches = await res.json();
            },
            changeTournament() { this.tournament = null; this.matches = []; this.match = null; this.tournamentQuery = ''; },
            sortBy(field) {
                if (this.sortField === field) { this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'; }
                else { this.sortField = field; this.sortDir = 'asc'; }
            },
            visibleMatches() {
                const term = this.matchFilter.toLowerCase();
                let list = this.matches.filter(m => ! term || (m.team_a + ' ' + m.team_b + ' ' + m.phase).toLowerCase().includes(term));
                const field = this.sortField, dir = this.sortDir === 'asc' ? 1 : -1;
                return list.slice().sort((a, b) => {
                    const av = a[field] ?? '', bv = b[field] ?? '';
                    if (av < bv) return -1 * dir;
                    if (av > bv) return 1 * dir;
                    return 0;
                });
            },
            pickMatch(m) { this.match = m; },
            storeUrl() { return `{{ url('/admin/tournaments') }}/${this.tournament.id}/matches/${this.match.id}/vods`; },
         }">

        <div class="relative mb-6" x-show="! tournament" @click.outside="tournamentResults = []">
            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.matches.fields.tournament') }}</label>
            <input type="text" x-model="tournamentQuery" @input.debounce.300ms="searchTournaments()" autocomplete="off"
                   placeholder="{{ __('admin.vods.matches.fields.tournament_search') }}"
                   class="w-full max-w-md bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <div x-show="tournamentResults.length" x-cloak
                 class="absolute z-20 mt-1 w-full max-w-md max-h-48 overflow-y-auto bg-[#0a0a0a] border border-white/10 rounded-lg shadow-xl">
                <template x-for="t in tournamentResults" :key="t.id">
                    <div @click="pickTournament(t)" x-text="t.label" class="px-4 py-2 text-sm text-white cursor-pointer hover:bg-white/5"></div>
                </template>
            </div>
        </div>

        <div x-show="tournament" x-cloak>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('admin.vods.matches.fields.tournament') }}</span>
                    <span class="text-sm text-white font-bold" x-text="tournament ? tournament.label : ''"></span>
                </div>
                <button type="button" @click="changeTournament()" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-white transition">
                    {{ __('admin.vods.matches.fields.change_tournament') }}
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <input type="text" x-model="matchFilter" placeholder="{{ __('admin.vods.matches.fields.match_search') }}"
                           class="w-full max-w-sm mb-3 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

                    <div class="overflow-x-auto border border-white/10 rounded-lg">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                    @foreach ([['phase', 'admin.matches.phase'], ['round_name', 'admin.matches.round_name'], ['team_a', 'admin.matches.teams'], ['status', 'admin.matches.status_column'], ['scheduled_at', 'admin.matches.scheduled_at']] as [$field, $label])
                                        <th class="px-3 py-2 cursor-pointer select-none hover:text-white transition" @click="sortBy('{{ $field }}')">
                                            {{ __($label) }}
                                            <span class="text-gc-yellow" x-show="sortField === '{{ $field }}'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    @endforeach
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="m in visibleMatches()" :key="m.id">
                                    <tr class="border-b border-white/10 last:border-0 hover:bg-white/5 cursor-pointer transition"
                                        :class="match && match.id === m.id ? 'bg-white/10' : ''" @click="pickMatch(m)">
                                        <td class="px-3 py-2 text-xs font-bold uppercase text-gray-400" x-text="m.phase"></td>
                                        <td class="px-3 py-2 text-xs uppercase text-gray-500" x-text="m.round_name || '—'"></td>
                                        <td class="px-3 py-2 text-white font-semibold" x-text="m.team_a + ' vs ' + m.team_b"></td>
                                        <td class="px-3 py-2 text-xs text-gray-300" x-text="m.status"></td>
                                        <td class="px-3 py-2 text-xs text-gray-400" x-text="m.scheduled_at ? new Date(m.scheduled_at).toLocaleString() : '{{ __('admin.matches.unknown_date') }}'"></td>
                                        <td class="px-3 py-2 text-right">
                                            <span x-show="match && match.id === m.id" class="text-gc-yellow font-black">✓</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="! visibleMatches().length">
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-500 text-xs">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <form method="POST" x-show="match" x-cloak :action="storeUrl()" class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-4 sticky top-6">
                        @csrf

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.url') }}</label>
                            <input type="url" name="url" required maxlength="2048" placeholder="https://…"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>

                        <div x-data="{
                                open: false, query: '', selected: '',
                                countries: @js($countries),
                                select(code, label) { this.selected = code; this.query = label; this.open = false; },
                                flagClass(code) { return code === '{{ \App\Support\Countries::INTERNATIONAL }}' ? 'un' : code; },
                             }" class="relative" @click.outside="open = false">
                            <label for="vod_wizard_language_code_query" class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">
                                {{ __('admin.vods.fields.language_code') }}
                            </label>
                            <input type="hidden" name="language_code" :value="selected">
                            <input id="vod_wizard_language_code_query" type="text" x-model="query" @focus="open = true" autocomplete="off" required
                                   placeholder="{{ __('admin.vods.fields.language_code_search') }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <div x-show="open" x-cloak
                                 class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto bg-[#0a0a0a] border border-white/10 rounded-lg shadow-xl">
                                <template x-for="[code, name] in Object.entries(countries)" :key="code">
                                    <div x-show="query === '' || (name + ' ' + code).toLowerCase().includes(query.toLowerCase())"
                                         @click="select(code, name + ' (' + code.toUpperCase() + ')')"
                                         class="flex items-center gap-2 px-4 py-2 text-sm text-white cursor-pointer hover:bg-white/5">
                                        <span class="fi shadow-sm flex-shrink-0" :class="'fi-' + flagClass(code)"></span>
                                        <span x-text="name + ' (' + code.toUpperCase() + ')'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="match && match.maps && match.maps.length">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.map') }}</label>
                            <select name="game_map_id"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                <option value="" style="background-color:#0a0a0a;color:#fff;">{{ __('admin.vods.fields.map_none') }}</option>
                                <template x-for="map in (match ? match.maps : [])" :key="map.id">
                                    <option :value="map.id" x-text="map.name" style="background-color:#0a0a0a;color:#fff;"></option>
                                </template>
                            </select>
                        </div>

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest px-8 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.vods.matches.link_submit') }}
                        </button>
                    </form>

                    <p x-show="! match" class="text-xs text-gray-500">{{ __('admin.vods.matches.fields.pick_match_hint') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
