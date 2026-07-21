{{--
    GC-Stats — Admin: players list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.players.title'))

@section('content')
    @if (session('created_player'))
        @php $createdPlayer = \App\Models\Player::find(session('created_player')) @endphp
        @if ($createdPlayer)
            <div class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
                <p class="text-xs text-white">{{ __('admin.players.create.success', ['name' => $createdPlayer->handle]) }}</p>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('admin.players.show', $createdPlayer) }}"
                       class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        {{ __('admin.players.manage') }}
                    </a>
                    <a href="{{ route('players.show', [$createdPlayer->id, str($createdPlayer->handle)->slug()]) }}" target="_blank" rel="noopener"
                       class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        {{ __('admin.players.public_page') }}
                    </a>
                </div>
            </div>
        @endif
    @endif

    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <form method="GET" action="{{ route('admin.players.index') }}" class="flex flex-wrap gap-2 flex-1 min-w-[200px]">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.players.search_placeholder') }}"
                   class="flex-1 max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

            <select name="sort" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="name" @selected($sort === 'name')>{{ __('admin.players.sort.name') }}</option>
                <option value="country" @selected($sort === 'country')>{{ __('admin.players.sort.country') }}</option>
                <option value="recent_activity" @selected($sort === 'recent_activity')>{{ __('admin.players.sort.recent_activity') }}</option>
            </select>

            <select name="active_within" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="" @selected($activeWithin === '')>{{ __('admin.players.active_within.any') }}</option>
                <option value="30d" @selected($activeWithin === '30d')>{{ __('admin.players.active_within.30d') }}</option>
                <option value="90d" @selected($activeWithin === '90d')>{{ __('admin.players.active_within.90d') }}</option>
                <option value="6m" @selected($activeWithin === '6m')>{{ __('admin.players.active_within.6m') }}</option>
                <option value="1y" @selected($activeWithin === '1y')>{{ __('admin.players.active_within.1y') }}</option>
            </select>

            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.players.search_submit') }}
            </button>
        </form>

        @can('players.create')
            <x-modal :title="__('admin.players.create.title')" max-width="max-w-sm">
                <x-slot:trigger>
                    <button type="button"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.players.create.title') }}
                    </button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('admin.players.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.players.create.name_label') }}
                        </label>
                        <input type="text" name="handle" required maxlength="255"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('handle')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{
                            open: false,
                            query: '',
                            selected: '',
                            countries: {{ \Illuminate\Support\Js::from($countries) }},
                            select(code, label) { this.selected = code; this.query = label; this.open = false; },
                            clear() { this.selected = ''; this.query = ''; this.open = false; },
                            flagClass(code) { return code === '{{ \App\Support\Countries::INTERNATIONAL }}' ? 'un' : code; },
                         }" class="relative" @click.away="open = false">
                        <label for="create_player_country_code_query" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.players.create.country_label') }}
                        </label>
                        <input type="hidden" name="country_code" :value="selected">
                        <input id="create_player_country_code_query" type="text" x-model="query" @focus="open = true" autocomplete="off"
                               placeholder="{{ __('player.edit.fields.country_code_search') }}"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        <div x-show="open" x-cloak
                             class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-bg-card border border-white/10 rounded-lg shadow-xl">
                            <div @click="clear()" class="px-4 py-2 text-xs text-gray-500 cursor-pointer hover:bg-white/5">
                                {{ __('player.edit.fields.country_code_none') }}
                            </div>
                            <template x-for="[code, name] in Object.entries(countries)" :key="code">
                                <div x-show="query === '' || (name + ' ' + code).toLowerCase().includes(query.toLowerCase())"
                                     @click="select(code, name + ' (' + code.toUpperCase() + ')')"
                                     class="flex items-center gap-2 px-4 py-2 text-sm text-white cursor-pointer hover:bg-white/5">
                                    <span class="fi shadow-sm flex-shrink-0" :class="'fi-' + flagClass(code)"></span>
                                    <span x-text="name + ' (' + code.toUpperCase() + ')'"></span>
                                </div>
                            </template>
                        </div>
                        @error('country_code')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-team-select name="team_id" :label="__('admin.players.create.team_label')" :teams="$teamOptions" :selected="old('team_id')" :placeholder="'—'" />

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.players.create.vlr_id_label') }}
                        </label>
                        <input type="number" name="vlr_id"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        @error('vlr_id')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.players.create.submit') }}
                    </button>
                </form>
            </x-modal>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto"
         x-data="GCS.sortableTable()">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3" @click="sortBy('handle')">
                        <span class="group inline-flex items-center gap-1 hover:text-white transition cursor-pointer select-none">
                            {{ __('admin.players.title') }}
                            @include('admin.partials.sort-arrows', ['col' => 'handle'])
                        </span>
                    </th>
                    <th class="px-4 py-3"></th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody x-ref="tbody">
                @forelse ($players as $player)
                    <tr data-row data-handle="{{ $player->handle }}" class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $player->handle }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('players.show', [$player->id, str($player->handle)->slug()]) }}" target="_blank" rel="noopener"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.players.public_page') }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.players.show', $player) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.players.manage') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-xs">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $players->links() }}
@endsection
