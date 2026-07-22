{{--
    GC-Stats — Admin: game map detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@php
    $editLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'maps.edit');
    $fetchLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'maps.fetch');
    $renewLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'maps.cache.renew');
    $resetLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'maps.reset');
    $deleteLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'maps.delete');
    $anyLocked = $editLocked
        || (auth()->user()->can('maps.fetch') && $fetchLocked)
        || (auth()->user()->can('maps.cache.renew') && $renewLocked)
        || (auth()->user()->can('maps.reset') && $resetLocked)
        || (auth()->user()->can('maps.delete') && $deleteLocked);
    $statsByTeam = [
        'teamA' => $map->playerStats->where('team_id', $match->team_a_id)->values(),
        'teamB' => $map->playerStats->where('team_id', $match->team_b_id)->values(),
    ];
    $bigBtn = 'font-bold uppercase text-xs tracking-widest px-5 py-3 rounded-lg transition active:scale-95 disabled:opacity-40';

    $resolveTeamId = function ($playerId) use ($map, $match, $teamAPlayers, $teamBPlayers) {
        $fromStats = $map->playerStats->firstWhere('player_id', $playerId);
        if ($fromStats) return $fromStats->team_id;
        if ($teamAPlayers->contains('id', $playerId)) return $match->team_a_id;
        if ($teamBPlayers->contains('id', $playerId)) return $match->team_b_id;
        return null;
    };

    $initialPlayerStats = $map->playerStats->map(fn ($s) => [
        'player_id' => $s->player_id, 'team_id' => $s->team_id, 'agent_name' => $s->agent_name,
        'kills' => $s->kills, 'deaths' => $s->deaths, 'assists' => $s->assists,
        'acs' => $s->acs, 'adr' => $s->adr, 'kast_percentage' => $s->kast_percentage,
        'first_kills' => $s->first_kills, 'first_deaths' => $s->first_deaths, 'headshot_percentage' => $s->headshot_percentage,
    ])->values();

    $initialRounds = $map->rounds->sortBy('round_number')->map(fn ($r) => [
        'round_number' => $r->round_number, 'winning_team' => $r->winning_team, 'win_type' => $r->win_type,
        'player_stats' => $r->playerStats->map(fn ($ps) => [
            'player_id' => $ps->player_id, 'team_id' => $resolveTeamId($ps->player_id),
            'kills' => $ps->kills, 'assists' => $ps->assists, 'score' => $ps->score,
            'loadout_value' => $ps->loadout_value, 'economy_spent' => $ps->economy_spent, 'economy_remaining' => $ps->economy_remaining,
            'weapon_id' => $ps->weapon_id, 'armor' => $ps->armor,
        ])->values(),
    ])->values();
@endphp

@section('title', \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status).' vs '.\App\Support\MatchDisplay::teamShortName($match->teamB, $match->status).$map->map_name)

@section('content')
    <a href="{{ route('admin.matches.show', [$tournament, $match]) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }} vs {{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}
    </a>

    <div class="relative flex flex-col items-center justify-center min-h-[130px] rounded-lg bg-cover bg-center p-4 text-white mb-6 overflow-hidden"
         style="background-image: linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 100%), url('/storage/maps/{{ strtolower($map->map_name) }}.webp')">
        <div class="z-10 flex items-center justify-center gap-8 w-full">
            <div class="flex flex-1 items-center justify-end gap-3 text-right">
                <h2 class="text-xl font-black uppercase leading-none">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</h2>
                @if ($match->teamA)
                    <img src="{{ $match->teamA->logo }}" alt="" class="h-12 w-12 object-contain">
                @endif
            </div>
            <span class="text-2xl font-black">{{ $match->team_a_score ?? 0 }} - {{ $match->team_b_score ?? 0 }}</span>
            <div class="flex flex-1 items-center gap-3">
                @if ($match->teamB)
                    <img src="{{ $match->teamB->logo }}" alt="" class="h-12 w-12 object-contain">
                @endif
                <h2 class="text-xl font-black uppercase leading-none">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</h2>
            </div>
        </div>
        <h6 class="z-10 mt-2 text-sm font-bold uppercase tracking-wide opacity-75">{{ $map->map_name }}</h6>
    </div>

    @if ($anyLocked)
        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.matches.finished_locked') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <div class="lg:col-span-5 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-4">{{ __('admin.matches.maps.basic_info') }}</h2>

            <fieldset @disabled($editLocked)>
                <form method="POST" action="{{ route('admin.matches.maps.update', [$tournament, $match, $map]) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <label class="block">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.maps.api_match_id') }}</span>
                        <input type="text" name="api_match_id" value="{{ old('api_match_id', $map->api_match_id) }}"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white font-mono focus:outline-none focus:border-gc-yellow transition">
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.team_a_score') }}</span>
                            <input type="number" name="team_a_score" value="{{ old('team_a_score', $map->team_a_score) }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        </label>
                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.team_b_score') }}</span>
                            <input type="number" name="team_b_score" value="{{ old('team_b_score', $map->team_b_score) }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.maps.order') }}</span>
                            <input type="number" name="order" value="{{ old('order', $map->order) }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        </label>
                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.maps.completed') }}</span>
                            <select name="is_completed"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                <option value="1" @selected(old('is_completed', $map->is_completed) == 1)>{{ __('admin.matches.maps.finished') }}</option>
                                <option value="0" @selected(old('is_completed', $map->is_completed) == 0)>{{ __('admin.matches.maps.not_finished') }}</option>
                            </select>
                        </label>
                    </div>

                    <button type="submit" class="w-full {{ $bigBtn }} bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.matches.edit.submit') }}
                    </button>
                </form>
            </fieldset>
        </div>

        <div class="lg:col-span-7 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl"
             @can('maps.fetch')
             x-data="{
                loading: false,
                error: null,
                missingValIds: null,
                isEsportEndpoint: false,
                teamColorOptions: null,
                teamColorPlayers: null,
                puuidMapping: {},
                rosterPlayers: {{ \Illuminate\Support\Js::from($rosterPlayers->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle, 'country_code' => $p->country_code])) }},

                fetchMap(extra = {}) {
                    this.loading = true;
                    this.error = null;

                    fetch({{ \Illuminate\Support\Js::from(route('admin.matches.maps.fetch', [$tournament, $match, $map])) }}, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': {{ \Illuminate\Support\Js::from(csrf_token()) }},
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(extra),
                    })
                        .then(async (res) => {
                            const data = await res.json().catch(() => ({}));

                            if (res.ok) {
                                window.location.reload();

                                return;
                            }

                            this.error = data.error ?? null;
                            this.missingValIds = data.missing_val_ids ?? null;
                            this.isEsportEndpoint = !! data.is_esport_endpoint;
                            this.teamColorOptions = data.available_colors ?? null;
                            this.teamColorPlayers = data.players ?? null;

                            if (this.missingValIds) {
                                this.puuidMapping = {};
                                this.missingValIds.forEach((p) => { this.puuidMapping[p.puuid] = ''; });
                            }
                        })
                        .finally(() => { this.loading = false; });
                },

                submitPlayerMapping() {
                    const mapping = {};

                    for (const [puuid, playerId] of Object.entries(this.puuidMapping)) {
                        if (playerId) mapping[puuid] = parseInt(playerId);
                    }

                    this.fetchMap({ puuid_mapping: mapping });
                },

                chooseColor(color) {
                    this.fetchMap({ team_a_color: color });
                },

                sortedColors() {
                    return [...(this.teamColorOptions || [])].sort((a, b) => (a === 'Red' ? -1 : b === 'Red' ? 1 : 0));
                },
             }"
             @endcan
        >
            <div x-show="loading" x-cloak class="flex items-center justify-center gap-3 py-10">
                <svg class="animate-spin h-5 w-5 text-gc-yellow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-xs font-black uppercase tracking-widest text-gray-400">{{ __('admin.matches.maps.fetching_in_progress') }}</span>
            </div>

            <div x-show="!loading">
            <div class="flex items-center justify-between mb-4 gap-2 flex-wrap">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.matches.maps.info_title') }}</h2>
                <div class="flex gap-2">
                    @can('maps.fetch')
                        <button type="button" @click="fetchMap()" :disabled="loading || {{ $fetchLocked ? 'true' : 'false' }}"
                                class="{{ $bigBtn }} bg-white/5 border border-white/10 text-white hover:bg-white/10">
                            <span x-text="loading ? '{{ __('admin.matches.maps.fetching') }}' : '{{ __('admin.matches.maps.fetch') }}'"></span>
                        </button>
                    @endcan
                    @can('maps.cache.renew')
                        <form method="POST" action="{{ route('admin.matches.maps.renew', [$tournament, $match, $map]) }}">
                            @csrf
                            <button type="submit" @disabled($renewLocked || ! $map->api_match_id)
                                    class="{{ $bigBtn }} bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.matches.maps.renew') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            @can('maps.fetch')
                <div x-show="error" x-cloak class="mb-4 bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-lg px-4 py-3" x-text="error"></div>

                <div x-show="missingValIds && missingValIds.length" x-cloak class="mb-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 space-y-3">
                    <p class="text-xs text-yellow-400">{{ __('admin.matches.maps.missing_val_ids_help') }}</p>
                    <template x-for="p in (missingValIds || [])" :key="p.puuid">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-white font-bold truncate" x-text="p.name"></p>
                                <p class="text-xs text-gray-500" x-text="(p.agent ?? '—') + ' — ' + (p.team ?? '—')"></p>
                            </div>
                            <div class="relative w-48"
                                 x-data="GCS.playerSearchPicker({
                                    searchUrl: {{ \Illuminate\Support\Js::from(route('admin.players.search')) }},
                                    internationalCode: {{ \Illuminate\Support\Js::from(\App\Support\Countries::INTERNATIONAL) }},
                                    initial: rosterPlayers,
                                    onPick: (player) => { puuidMapping[p.puuid] = player.id; },
                                 })"
                                 @click.outside="open = false">
                                <input type="text"
                                       x-model="query"
                                       @focus="open = true"
                                       @input.debounce.300ms="search()"
                                       placeholder="{{ __('admin.matches.maps.select_player') }}"
                                       autocomplete="off"
                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

                                <div x-show="open && results.length" x-cloak
                                     class="absolute z-10 mt-1 w-48 bg-bg-card border border-white/10 rounded-lg shadow-xl max-h-48 overflow-y-auto">
                                    <template x-for="rp in results" :key="rp.id">
                                        <button type="button" @click="pick(rp)"
                                                class="flex w-full items-center gap-2 text-left px-3 py-2 text-xs text-white hover:bg-white/5 transition">
                                            <span class="fi shadow-sm flex-shrink-0" :class="'fi-' + flagClass(rp.country_code)"></span>
                                            <span class="truncate" x-text="rp.handle + ' (' + rp.id + ')'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="submitPlayerMapping()" :disabled="loading"
                            class="w-full font-bold uppercase text-xs tracking-widest py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.matches.maps.save_and_retry') }}
                    </button>
                </div>

                <div x-show="teamColorOptions && teamColorOptions.length" x-cloak class="mb-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 space-y-3">
                    <p class="text-xs text-yellow-400">{{ __('admin.matches.maps.team_color_help', ['team' => \App\Support\MatchDisplay::teamName($match->teamA, $match->status)]) }}</p>

                    <div x-show="(teamColorPlayers || []).length" x-cloak class="grid grid-cols-2 gap-x-4 gap-y-2">
                        <template x-for="color in sortedColors()" :key="color">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-500" x-text="color"></p>
                                <template x-for="p in (teamColorPlayers || []).filter((pl) => pl.team === color)" :key="p.puuid">
                                    <div class="text-xs truncate text-white font-semibold" x-text="p.name"></div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-2">
                        <template x-for="color in sortedColors()" :key="color">
                            <button type="button" @click="chooseColor(color)" :disabled="loading" x-text="color"
                                    class="flex-1 font-bold uppercase text-xs tracking-widest py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10"></button>
                        </template>
                    </div>
                </div>
            @endcan
            </div>

            <div class="grid grid-cols-[1fr_auto_1fr] gap-6">
                <div>
                    <h4 class="mb-3 text-center text-xs font-black uppercase text-gray-500">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</h4>
                    <div class="grid grid-cols-[1fr_1fr_auto] items-center gap-x-3 gap-y-2">
                        <span class="text-[10px] font-black uppercase text-gray-500">{{ __('admin.matches.maps.handle') }}</span>
                        <span class="text-[10px] font-black uppercase text-gray-500">{{ __('admin.matches.maps.riot_id') }}</span>
                        <span class="text-[10px] font-black uppercase text-gray-500">{{ __('admin.matches.maps.agent') }}</span>
                        @foreach ($statsByTeam['teamA'] as $stat)
                            <span class="truncate border-t border-white/10 pt-2 text-sm font-bold text-white">{{ $stat->player->handle ?? '—' }}</span>
                            <span class="truncate border-t border-white/10 pt-2 text-sm font-bold text-white">{{ $stat->val_name ?? '—' }}</span>
                            <span class="border-t border-white/10 pt-2 text-sm text-gray-400">{{ $stat->agent_name ?? '—' }}</span>
                        @endforeach
                    </div>
                    @if ($statsByTeam['teamA']->isEmpty())
                        <p class="py-2 text-center text-xs text-gray-500">{{ __('admin.matches.maps.no_player_data') }}</p>
                    @endif
                </div>
                <div class="w-px bg-white/10"></div>
                <div>
                    <h4 class="mb-3 text-center text-xs font-black uppercase text-gray-500">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</h4>
                    <div class="grid grid-cols-[1fr_1fr_auto] items-center gap-x-3 gap-y-2">
                        <span class="text-[10px] font-black uppercase text-gray-500">{{ __('admin.matches.maps.handle') }}</span>
                        <span class="text-[10px] font-black uppercase text-gray-500">{{ __('admin.matches.maps.riot_id') }}</span>
                        <span class="text-[10px] font-black uppercase text-gray-500">{{ __('admin.matches.maps.agent') }}</span>
                        @foreach ($statsByTeam['teamB'] as $stat)
                            <span class="truncate border-t border-white/10 pt-2 text-sm font-bold text-white">{{ $stat->player->handle ?? '—' }}</span>
                            <span class="truncate border-t border-white/10 pt-2 text-sm font-bold text-white">{{ $stat->val_name ?? '—' }}</span>
                            <span class="border-t border-white/10 pt-2 text-sm text-gray-400">{{ $stat->agent_name ?? '—' }}</span>
                        @endforeach
                    </div>
                    @if ($statsByTeam['teamB']->isEmpty())
                        <p class="py-2 text-center text-xs text-gray-500">{{ __('admin.matches.maps.no_player_data') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl mb-6"
         x-data="GCS.manualMapStats({
            updateUrl: {{ \Illuminate\Support\Js::from(route('admin.matches.maps.stats.update', [$tournament, $match, $map])) }},
            teamA: {{ \Illuminate\Support\Js::from(['id' => $match->team_a_id, 'name' => \App\Support\MatchDisplay::teamName($match->teamA, $match->status)]) }},
            teamB: {{ \Illuminate\Support\Js::from(['id' => $match->team_b_id, 'name' => \App\Support\MatchDisplay::teamName($match->teamB, $match->status)]) }},
            teamAPlayers: {{ \Illuminate\Support\Js::from($teamAPlayers) }},
            teamBPlayers: {{ \Illuminate\Support\Js::from($teamBPlayers) }},
            initialPlayerStats: {{ \Illuminate\Support\Js::from($initialPlayerStats) }},
            initialRounds: {{ \Illuminate\Support\Js::from($initialRounds) }},
            errorText: {{ \Illuminate\Support\Js::from(__('admin.matches.maps.manual_stats.error')) }},
         })"
    >
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-4">{{ __('admin.matches.maps.manual_stats.title') }}</h2>

        <fieldset @disabled($editLocked) class="space-y-6">
            <div x-show="error" x-cloak class="bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-lg px-4 py-3" x-text="error"></div>

            <div>
                <h3 class="text-xs font-black uppercase text-white mb-3">{{ __('admin.matches.maps.manual_stats.player_stats') }}</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach (['teamA', 'teamB'] as $teamKey)
                        <div class="overflow-x-auto">
                            <h4 class="mb-2 text-center text-[10px] font-black uppercase text-gray-500">{{ \App\Support\MatchDisplay::teamName($teamKey === 'teamA' ? $match->teamA : $match->teamB, $match->status) }}</h4>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-white/10 text-[9px] font-black uppercase text-gray-500">
                                        <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.player') }}</th>
                                        <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.agent') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.kills') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.deaths') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.assists') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.acs') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.adr') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.kast') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.first_kills') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.first_deaths') }}</th>
                                        <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.headshot') }}</th>
                                        <th class="pb-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(stat, index) in mainStatsByTeam.{{ $teamKey }}" :key="index">
                                        <tr class="border-b border-white/5">
                                            <td class="py-1.5 pr-2">
                                                <select x-model="stat.player_id" class="w-full h-8 rounded-md border border-white/10 bg-white/5 px-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                                                    <option value="">—</option>
                                                    <template x-for="p in {{ \Illuminate\Support\Js::from($pickerPlayers) }}" :key="p.id">
                                                        <option :value="p.id" :selected="String(stat.player_id) === String(p.id)" x-text="p.handle"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="py-1.5 pr-2">
                                                <select x-model="stat.agent_name" class="w-full h-8 rounded-md border border-white/10 bg-white/5 px-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                                                    <option value="">—</option>
                                                    <template x-for="a in {{ \Illuminate\Support\Js::from($agentPool) }}" :key="a">
                                                        <option :value="a" :selected="stat.agent_name === a" x-text="a"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.kills" min="0" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.deaths" min="0" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.assists" min="0" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.acs" min="0" step="0.1" placeholder="—" class="w-14 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.adr" min="0" step="0.1" placeholder="—" class="w-14 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.kast_percentage" min="0" max="100" step="0.01" placeholder="—" class="w-14 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.first_kills" min="0" placeholder="—" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.first_deaths" min="0" placeholder="—" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5 pr-2"><input type="number" x-model="stat.headshot_percentage" min="0" max="100" step="0.01" placeholder="—" class="w-14 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                            <td class="py-1.5">
                                                <button type="button" @click="removePlayerRow(stat)" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-red-500/10 hover:text-red-400 transition">
                                                    @svg('fas-xmark', 'w-3 h-3', ['aria-hidden' => 'true'])
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-6 border-t border-white/10">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-black uppercase text-white">
                        {{ __('admin.matches.maps.manual_stats.rounds') }}
                        <span class="ml-1 text-[10px] font-normal normal-case text-gray-500">{{ __('admin.matches.maps.manual_stats.rounds_optional') }}</span>
                    </h3>
                    <button type="button" @click="addRound()" class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        {{ __('admin.matches.maps.manual_stats.add_round') }}
                    </button>
                </div>

                <p x-show="rounds.length === 0" class="text-xs text-gray-500">{{ __('admin.matches.maps.manual_stats.no_rounds') }}</p>

                <div x-show="rounds.length > 0" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-white/10 text-[9px] font-black uppercase text-gray-500">
                                <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.round_number') }}</th>
                                <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.winner') }}</th>
                                <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.win_type') }}</th>
                                <th class="pb-2 pr-2"></th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(round, i) in rounds" :key="i">
                                <tr class="border-b border-white/5">
                                    <td class="py-1.5 pr-2"><input type="number" x-model="round.round_number" min="1" class="w-16 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                    <td class="py-1.5 pr-2">
                                        <select x-model="round.winning_team" class="w-40 h-8 rounded-md border border-white/10 bg-white/5 px-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                                            <option value="">{{ __('admin.matches.maps.manual_stats.select_team') }}</option>
                                            <option :value="{{ $match->team_a_id }}" :selected="String(round.winning_team) === String({{ $match->team_a_id }})">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</option>
                                            <option :value="{{ $match->team_b_id }}" :selected="String(round.winning_team) === String({{ $match->team_b_id }})">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</option>
                                        </select>
                                    </td>
                                    <td class="py-1.5 pr-2">
                                        <select x-model="round.win_type" class="w-36 h-8 rounded-md border border-white/10 bg-white/5 px-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                                            <option value="">—</option>
                                            <template x-for="w in {{ \Illuminate\Support\Js::from($winTypePool) }}" :key="w">
                                                <option :value="w" :selected="round.win_type === w" x-text="w"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="py-1.5 pr-2">
                                        <button type="button" @click="openRoundEditor(i)" class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10 whitespace-nowrap">
                                            {{ __('admin.matches.maps.manual_stats.edit_player_stats') }}
                                        </button>
                                    </td>
                                    <td class="py-1.5">
                                        <button type="button" @click="removeRound(i)" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-red-500/10 hover:text-red-400 transition">
                                            @svg('fas-xmark', 'w-3 h-3', ['aria-hidden' => 'true'])
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-white/10">
                <button type="button" @click="submit()" :disabled="submitting"
                        class="{{ $bigBtn }} bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                    <span x-text="submitting ? '{{ __('admin.matches.maps.manual_stats.saving') }}' : '{{ __('admin.matches.maps.manual_stats.save') }}'"></span>
                </button>
            </div>
        </fieldset>

        <template x-teleport="body">
            <div x-show="editingRoundIndex !== null" x-cloak
                 class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                 @keydown.escape.window="editingRoundIndex = null">
                <div @click.away="editingRoundIndex = null" role="dialog" aria-modal="true"
                     class="w-full max-w-6xl max-h-[90vh] overflow-y-auto bg-bg-card border border-white/10 rounded-xl p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">
                        {{ __('admin.matches.maps.manual_stats.round_number') }} <span x-text="editingRound?.round_number"></span> — {{ __('admin.matches.maps.manual_stats.edit_player_stats') }}
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach (['teamA', 'teamB'] as $teamKey)
                            <div class="overflow-x-auto">
                                <h4 class="mb-2 text-center text-[10px] font-black uppercase text-gray-500">{{ \App\Support\MatchDisplay::teamName($teamKey === 'teamA' ? $match->teamA : $match->teamB, $match->status) }}</h4>
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b border-white/10 text-[9px] font-black uppercase text-gray-500">
                                            <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.player') }}</th>
                                            <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.kills') }}</th>
                                            <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.assists') }}</th>
                                            <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.score') }}</th>
                                            <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.loadout') }}</th>
                                            <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.spent') }}</th>
                                            <th class="pb-2 pr-2 text-center">{{ __('admin.matches.maps.manual_stats.remaining') }}</th>
                                            <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.weapon') }}</th>
                                            <th class="pb-2 pr-2 text-left">{{ __('admin.matches.maps.manual_stats.armor') }}</th>
                                            <th class="pb-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(ps, statIndex) in editingRoundByTeam.{{ $teamKey }}" :key="statIndex">
                                            <tr class="border-b border-white/5">
                                                <td class="py-1.5 pr-2">
                                                    <select x-model="ps.player_id" class="w-28 h-8 rounded-md border border-white/10 bg-white/5 px-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                                                        <option value="">—</option>
                                                        <template x-for="p in {{ \Illuminate\Support\Js::from($pickerPlayers) }}" :key="p.id">
                                                            <option :value="p.id" :selected="String(ps.player_id) === String(p.id)" x-text="p.handle"></option>
                                                        </template>
                                                    </select>
                                                </td>
                                                <td class="py-1.5 pr-2"><input type="number" x-model="ps.kills" min="0" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                                <td class="py-1.5 pr-2"><input type="number" x-model="ps.assists" min="0" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                                <td class="py-1.5 pr-2"><input type="number" x-model="ps.score" min="0" class="w-12 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                                <td class="py-1.5 pr-2"><input type="number" x-model="ps.loadout_value" min="-1" class="w-14 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                                <td class="py-1.5 pr-2"><input type="number" x-model="ps.economy_spent" min="-1" class="w-14 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                                <td class="py-1.5 pr-2"><input type="number" x-model="ps.economy_remaining" min="-1" class="w-14 h-8 rounded-md border border-white/10 bg-white/5 px-1 text-center text-[11px] text-white focus:outline-none focus:border-gc-yellow [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"></td>
                                                <td class="py-1.5 pr-2">
                                                    <select x-model="ps.weapon_id" class="w-24 h-8 rounded-md border border-white/10 bg-white/5 px-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                                                        <option value="">—</option>
                                                        <template x-for="w in {{ \Illuminate\Support\Js::from($weaponPool) }}" :key="w">
                                                            <option :value="w" :selected="ps.weapon_id === w" x-text="w"></option>
                                                        </template>
                                                    </select>
                                                </td>
                                                <td class="py-1.5 pr-2">
                                                    <select x-model="ps.armor" class="w-24 h-8 rounded-md border border-white/10 bg-white/5 px-1.5 text-[11px] text-white focus:outline-none focus:border-gc-yellow [color-scheme:dark]">
                                                        <option value="">—</option>
                                                        <template x-for="ar in {{ \Illuminate\Support\Js::from($armorPool) }}" :key="ar">
                                                            <option :value="ar" :selected="ps.armor === ar" x-text="ar"></option>
                                                        </template>
                                                    </select>
                                                </td>
                                                <td class="py-1.5">
                                                    <button type="button" @click="removeRoundPlayerRow(ps)" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-red-500/10 hover:text-red-400 transition">
                                                        @svg('fas-xmark', 'w-3 h-3', ['aria-hidden' => 'true'])
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="editingRoundIndex = null"
                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.matches.maps.manual_stats.done') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @if (auth()->user()->can('maps.reset') || auth()->user()->can('maps.delete'))
        <div class="bg-bg-card border border-red-500/20 rounded-lg p-6 shadow-xl">
            <h2 class="text-xs font-black uppercase tracking-widest text-red-400 mb-4">{{ __('admin.matches.maps.danger_zone') }}</h2>

            <div class="flex flex-wrap gap-3">
                @can('maps.reset')
                    <form method="POST" action="{{ route('admin.matches.maps.reset', [$tournament, $match, $map]) }}">
                        @csrf
                        <x-confirm-modal
                            :title="__('admin.matches.maps.reset_map')"
                            :body="__('admin.matches.maps.reset_confirm')"
                            :trigger-label="__('admin.matches.maps.reset_map')"
                            :submit-label="__('admin.matches.maps.reset_map')"
                            :trigger-class="$bigBtn.' bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10'.($resetLocked ? ' pointer-events-none opacity-40' : '')"
                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                        />
                    </form>
                @endcan
                @can('maps.delete')
                    <form method="POST" action="{{ route('admin.matches.maps.destroy', [$tournament, $match, $map]) }}">
                        @csrf
                        @method('DELETE')
                        <x-confirm-modal
                            :title="__('admin.matches.maps.delete')"
                            :body="__('admin.matches.maps.delete_confirm')"
                            :trigger-label="__('admin.matches.maps.delete')"
                            :submit-label="__('admin.matches.maps.delete')"
                            :trigger-class="$bigBtn.' bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10'.($deleteLocked ? ' pointer-events-none opacity-40' : '')"
                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                        />
                    </form>
                @endcan
            </div>
        </div>
    @endif
@endsection
