{{--
    GC-Stats — Admin: link channels to matches (tournament -> matches -> channels wizard)

    Lets a user reach any match (an active tournament's, or any tournament's
    if they hold streams.matches.link — see Admin\MatchStreamController)
    without going through the admin match list, which publishers can't
    reach. Once a tournament is picked, every one of its matches is fetched
    once and rendered as a sortable table — same columns as
    admin/matches/index.blade.php (phase, round, teams, status, date) —
    sorted/filtered client-side against that already-fetched list.

    Unlike the VOD wizard, both matches AND channels here are multi-select:
    picking N matches and M channels links every channel to every match in
    one submit (Admin\MatchStreamController::linkMany() — a cross-product),
    since the same set of channels commonly needs linking across a whole
    batch of matches (e.g. every remaining match of a stage).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.streams.matches.create_title'))

@section('content')
    <a href="{{ route('admin.streams.matches.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.streams.matches.title') }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ __('admin.streams.matches.create_title') }}</h1>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl"
         x-data="{
            tournament: null, tournamentQuery: '', tournamentResults: [],
            matches: [], matchFilter: '', sortField: 'scheduled_at', sortDir: 'desc',
            selectedMatches: [],
            channels: [], channelQuery: '', channelResults: [],
            async searchTournaments() {
                if (this.tournamentQuery.length < 2) { this.tournamentResults = []; return; }
                const res = await fetch(`{{ route('admin.streams.matches.search-tournaments') }}?q=${encodeURIComponent(this.tournamentQuery)}`);
                this.tournamentResults = await res.json();
            },
            async pickTournament(t) {
                this.tournament = t; this.tournamentResults = []; this.tournamentQuery = t.label;
                this.selectedMatches = [];
                const res = await fetch(`{{ route('admin.streams.matches.search-matches') }}?tournament_id=${t.id}`);
                this.matches = await res.json();
            },
            changeTournament() { this.tournament = null; this.matches = []; this.selectedMatches = []; this.tournamentQuery = ''; },
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
            isSelected(m) { return this.selectedMatches.some(s => s.id === m.id); },
            toggleMatch(m) {
                if (this.isSelected(m)) { this.selectedMatches = this.selectedMatches.filter(s => s.id !== m.id); }
                else { this.selectedMatches.push(m); }
            },
            selectAllVisible() { this.selectedMatches = this.visibleMatches(); },
            clearSelection() { this.selectedMatches = []; },
            async searchChannels() {
                if (this.channelQuery.length < 2) { this.channelResults = []; return; }
                const res = await fetch(`{{ route('admin.matches.streams.search') }}?q=${encodeURIComponent(this.channelQuery)}`);
                this.channelResults = (await res.json()).filter(c => ! this.channels.some(s => s.id === c.id));
            },
            addChannel(c) { this.channels.push(c); this.channelResults = []; this.channelQuery = ''; },
            removeChannel(id) { this.channels = this.channels.filter(c => c.id !== id); },
         }">

        <div class="relative mb-6" x-show="! tournament" @click.outside="tournamentResults = []">
            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.streams.matches.fields.tournament') }}</label>
            <input type="text" x-model="tournamentQuery" @input.debounce.300ms="searchTournaments()" autocomplete="off"
                   placeholder="{{ __('admin.streams.matches.fields.tournament_search') }}"
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
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('admin.streams.matches.fields.tournament') }}</span>
                    <span class="text-sm text-white font-bold" x-text="tournament ? tournament.label : ''"></span>
                </div>
                <button type="button" @click="changeTournament()" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-white transition">
                    {{ __('admin.streams.matches.fields.change_tournament') }}
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <input type="text" x-model="matchFilter" placeholder="{{ __('admin.streams.matches.fields.match_search') }}"
                               class="w-full max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500" x-text="selectedMatches.length + ' {{ __('admin.streams.matches.fields.selected') }}'"></span>
                            <button type="button" @click="selectAllVisible()" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-white transition">
                                {{ __('admin.streams.matches.fields.select_all') }}
                            </button>
                            <button type="button" @click="clearSelection()" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-white transition">
                                {{ __('admin.streams.matches.fields.clear_selection') }}
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-white/10 rounded-lg">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                    <th class="px-3 py-2 w-8"></th>
                                    @foreach ([['phase', 'admin.matches.phase'], ['round_name', 'admin.matches.round_name'], ['team_a', 'admin.matches.teams'], ['status', 'admin.matches.status_column'], ['scheduled_at', 'admin.matches.scheduled_at']] as [$field, $label])
                                        <th class="px-3 py-2 cursor-pointer select-none hover:text-white transition" @click="sortBy('{{ $field }}')">
                                            {{ __($label) }}
                                            <span class="text-gc-yellow" x-show="sortField === '{{ $field }}'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="m in visibleMatches()" :key="m.id">
                                    <tr class="border-b border-white/10 last:border-0 hover:bg-white/5 cursor-pointer transition"
                                        :class="isSelected(m) ? 'bg-white/10' : ''" @click="toggleMatch(m)">
                                        <td class="px-3 py-2">
                                            <input type="checkbox" :checked="isSelected(m)" @click.stop="toggleMatch(m)"
                                                   class="rounded-sm border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
                                        </td>
                                        <td class="px-3 py-2 text-xs font-bold uppercase text-gray-400" x-text="m.phase"></td>
                                        <td class="px-3 py-2 text-xs uppercase text-gray-500" x-text="m.round_name || '—'"></td>
                                        <td class="px-3 py-2 text-white font-semibold" x-text="m.team_a + ' vs ' + m.team_b"></td>
                                        <td class="px-3 py-2 text-xs text-gray-300" x-text="m.status"></td>
                                        <td class="px-3 py-2 text-xs text-gray-400" x-text="m.scheduled_at ? new Date(m.scheduled_at).toLocaleString() : '{{ __('admin.matches.unknown_date') }}'"></td>
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
                    <form method="POST" action="{{ route('admin.streams.matches.link') }}" class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-4 sticky top-6">
                        @csrf
                        <template x-for="m in selectedMatches" :key="m.id">
                            <input type="hidden" name="match_id[]" :value="m.id">
                        </template>

                        <div class="relative" @click.outside="channelResults = []">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.streams.matches.fields.channel') }}</label>

                            <div class="flex flex-wrap gap-2 mb-2" x-show="channels.length">
                                <template x-for="c in channels" :key="c.id">
                                    <span class="inline-flex items-center gap-1.5 bg-white/10 border border-white/10 rounded-full px-3 py-1 text-xs text-white">
                                        <input type="hidden" name="stream_channel_id[]" :value="c.id">
                                        <span x-text="c.label"></span>
                                        <button type="button" @click="removeChannel(c.id)" class="text-gray-400 hover:text-red-400 transition">&times;</button>
                                    </span>
                                </template>
                            </div>

                            <input type="text" x-model="channelQuery" @input.debounce.300ms="searchChannels()" autocomplete="off"
                                   placeholder="{{ __('admin.streams.matches.fields.channel_search') }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <div x-show="channelResults.length" x-cloak
                                 class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto bg-[#0a0a0a] border border-white/10 rounded-lg shadow-xl">
                                <template x-for="c in channelResults" :key="c.id">
                                    <div @click="addChannel(c)" x-text="c.label" class="px-4 py-2 text-sm text-white cursor-pointer hover:bg-white/5"></div>
                                </template>
                            </div>
                        </div>

                        <button type="submit" :disabled="! selectedMatches.length || ! channels.length"
                                class="w-full font-bold uppercase text-xs tracking-widest px-8 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] disabled:opacity-30 disabled:pointer-events-none">
                            {{ __('admin.streams.matches.link_submit') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
