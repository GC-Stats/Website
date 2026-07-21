{{--
    GC-Stats — Admin: match map veto

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@php
    $locked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'matches.veto.edit');
    $rows = array_values(old('maps', $vetoSlots));
    $teamAId = $match->team_a_id;
    $teamBId = $match->team_b_id;
@endphp

@section('title', __('admin.matches.veto.title').' — '.\App\Support\MatchDisplay::teamShortName($match->teamA, $match->status).' vs '.\App\Support\MatchDisplay::teamShortName($match->teamB, $match->status))

@section('content')
    <a href="{{ route('admin.matches.show', [$tournament, $match]) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }} vs {{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}
    </a>

    <div class="flex items-center justify-center gap-6 border-b border-white/10 pb-6 mb-6">
        <div class="flex flex-1 items-center justify-end gap-3 text-right">
            <div>
                <h2 class="text-xl font-black uppercase leading-none text-white">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</h2>
                <div class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ __('admin.matches.team_a') }}</div>
            </div>
            @if ($match->teamA)
                <img src="{{ $match->teamA->logo }}" alt="" class="h-14 w-14 object-contain">
            @endif
        </div>

        <div class="text-3xl font-black italic text-gray-500">VS</div>

        <div class="flex flex-1 items-center gap-3">
            @if ($match->teamB)
                <img src="{{ $match->teamB->logo }}" alt="" class="h-14 w-14 object-contain">
            @endif
            <div>
                <h2 class="text-xl font-black uppercase leading-none text-white">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</h2>
                <div class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ __('admin.matches.team_b') }}</div>
            </div>
        </div>
    </div>

    @if ($locked)
        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.matches.finished_locked') }}
        </div>
    @endif

    <div
        x-data="{
            teamAId: {{ \Illuminate\Support\Js::from($teamAId ? (string) $teamAId : null) }},
            teamBId: {{ \Illuminate\Support\Js::from($teamBId ? (string) $teamBId : null) }},
            mapPool: {{ \Illuminate\Support\Js::from($mapPool) }},
            firstTeam: '',
            rows: {{ \Illuminate\Support\Js::from(collect($rows)->map(fn ($r) => array_merge($r, ['mapOpen' => false, 'mapQuery' => '']))->values()) }},

            usedMaps(index) {
                return this.rows.filter((_, i) => i !== index).map(r => r.map_name).filter(m => m !== 'none');
            },

            filteredMaps(row, index) {
                const used = this.usedMaps(index);
                const q = row.mapQuery.toLowerCase();
                return this.mapPool
                    .filter(m => ! used.includes(m) || m === row.map_name)
                    .filter(m => q.length < 1 || m.toLowerCase().includes(q));
            },

            openMap(row) {
                this.rows.forEach(r => { r.mapOpen = false; });
                row.mapOpen = true;
                row.mapQuery = '';
            },

            selectMap(row, name) {
                row.map_name = name;
                row.mapOpen = false;
            },

            canPickSide(row) {
                return row.type === 'pick' || row.type === 'decider';
            },

            oppositeTeam(row) {
                if (row.team === 'none' || ! row.team) return '';
                return row.team === this.teamAId ? this.teamBId : this.teamAId;
            },

            defaultSidePickedBy(row) {
                if (this.canPickSide(row) && ! row.side_picked_by) {
                    row.side_picked_by = this.oppositeTeam(row);
                }
            },

            applyFirstTeam() {
                if (! this.firstTeam) return;
                const other = this.firstTeam === this.teamAId ? this.teamBId : this.teamAId;
                this.rows.forEach((row, i) => {
                    row.team = (i % 2 === 0) ? this.firstTeam : other;
                    this.defaultSidePickedBy(row);
                });
            },
        }"
    >
        <div class="mb-6 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.veto.first_action') }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <select x-model="firstTeam" @change="applyFirstTeam()" @if($locked) disabled @endif
                        class="bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition disabled:opacity-40 [color-scheme:dark]">
                    <option value="">{{ __('admin.matches.veto.select_team') }}</option>
                    @if ($match->teamA)
                        <option value="{{ $teamAId }}">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</option>
                    @endif
                    @if ($match->teamB)
                        <option value="{{ $teamBId }}">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</option>
                    @endif
                </select>
                <p class="text-xs text-gray-500">{{ __('admin.matches.veto.first_action_help') }}</p>
            </div>
        </div>

        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.matches.veto.incomplete_warning') }}
        </div>

        <fieldset @disabled($locked)>
            <form method="POST" action="{{ route('admin.matches.veto.update', [$tournament, $match]) }}" class="space-y-3">
                @csrf
                @method('PUT')

                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
                        <div class="grid grid-cols-1 md:grid-cols-[80px_1fr_1fr_1fr_1fr_1fr] gap-3 items-start">
                            <span class="text-xs font-black uppercase tracking-tight text-white pt-2.5" x-text="'{{ __('admin.matches.veto.map_label') }} ' + (index + 1)"></span>

                            <select :name="`maps[${index}][team]`" x-model="row.team" @change="defaultSidePickedBy(row)"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                <option value="none">{{ __('admin.matches.veto.select_team') }}</option>
                                @if ($match->teamA)
                                    <option value="{{ $teamAId }}">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</option>
                                @endif
                                @if ($match->teamB)
                                    <option value="{{ $teamBId }}">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</option>
                                @endif
                            </select>

                            <div class="relative">
                                <button type="button" @click="openMap(row)" @click.outside="row.mapOpen = false"
                                        class="w-full flex items-center justify-between bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                    <span x-text="row.map_name === 'none' ? '{{ __('admin.matches.veto.select_map') }}' : row.map_name" class="truncate"></span>
                                    <svg class="w-3 h-3 text-gray-500 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <input type="hidden" :name="`maps[${index}][map_name]`" x-model="row.map_name">

                                <div x-show="row.mapOpen" x-cloak
                                     class="absolute z-10 mt-1 w-full bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-hidden">
                                    <input type="text" x-model="row.mapQuery" placeholder="{{ __('admin.matches.timezone_search') }}" x-ref="mapSearch"
                                           class="w-full bg-white/5 border-b border-white/10 px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                                    <div class="max-h-40 overflow-y-auto">
                                        <button type="button" @click="selectMap(row, 'none')"
                                                class="block w-full text-left px-3 py-1.5 text-xs text-gray-500 hover:bg-white/5 transition">—</button>
                                        <template x-for="mapName in filteredMaps(row, index)" :key="mapName">
                                            <button type="button" @click="selectMap(row, mapName)" x-text="mapName"
                                                    class="block w-full text-left px-3 py-1.5 text-xs text-white hover:bg-white/5 transition"></button>
                                        </template>
                                        <p x-show="filteredMaps(row, index).length === 0" class="px-3 py-2 text-xs text-gray-500">—</p>
                                    </div>
                                </div>
                            </div>

                            <select :name="`maps[${index}][type]`" x-model="row.type" @change="defaultSidePickedBy(row)"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                <option value="none">{{ __('admin.matches.veto.select_type') }}</option>
                                <option value="ban">{{ __('admin.matches.veto.ban') }}</option>
                                <option value="pick">{{ __('admin.matches.veto.pick') }}</option>
                                <option value="decider">{{ __('admin.matches.veto.decider') }}</option>
                            </select>

                            <select :name="`maps[${index}][side]`" x-model="row.side" :disabled="! canPickSide(row)"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition disabled:opacity-40 [color-scheme:dark]">
                                <option value="">{{ __('admin.matches.veto.select_side') }}</option>
                                <option value="ATK">{{ __('admin.matches.veto.atk') }}</option>
                                <option value="DEF">{{ __('admin.matches.veto.def') }}</option>
                            </select>

                            <select :name="`maps[${index}][side_picked_by]`" x-model="row.side_picked_by" :disabled="! canPickSide(row)"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition disabled:opacity-40 [color-scheme:dark]">
                                <option value="">{{ __('admin.matches.veto.side_picked_by') }}</option>
                                @if ($match->teamA)
                                    <option value="{{ $teamAId }}">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</option>
                                @endif
                                @if ($match->teamB)
                                    <option value="{{ $teamBId }}">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="font-bold uppercase text-xs tracking-widest px-6 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.matches.veto.save') }}
                    </button>
                </div>
            </form>
        </fieldset>
    </div>
@endsection
