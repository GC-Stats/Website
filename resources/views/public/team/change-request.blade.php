{{--
    GC-Stats — Team: suggest an edit

    Dedicated page (not a modal) letting any authenticated user propose a
    correction to a team's profile — every proposed field goes through the
    moderated ChangeRequest queue, never applied directly. Mirrors
    resources/views/public/player/change-request.blade.php. See
    App\Http\Controllers\Auth\TeamChangeRequestController.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('team.change_request.title', ['team' => $team->name]))

@php $teamParams = [$team->id, Str::routeSlug($team->name, $team->id)]; @endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <div class="border-b border-border-subtle pb-6">
                <a href="{{ route('teams.show', $teamParams) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-4">
                    &larr; {{ $team->name }}
                </a>
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('team.change_request.title', ['team' => $team->name]) }}</h1>
                <p class="text-sm text-gray-400 mt-2">{{ __('team.change_request.intro') }}</p>
            </div>

            @if (session('status') === 'change-request-submitted')
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __('team.change_request.submitted_status') }}
                </div>
            @endif

            @if ($errors->has('change_request'))
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3">
                    {{ $errors->first('change_request') }}
                </div>
            @endif

            <form method="POST" action="{{ route('teams.change-requests.store', $team->id) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.change_request.profile_section') }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.edit.fields.name') }}
                            </label>
                            <input id="name" type="text" name="name" value="{{ old('name', $team->name) }}" required
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('name')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="short_name" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.edit.fields.short_name') }}
                            </label>
                            <input id="short_name" type="text" name="short_name" value="{{ old('short_name', $team->short_name) }}"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('short_name')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        @php
                            $selectedCountryCode = Str::lower(old('country_code', $team->country_code) ?? '') ?: null;
                            $selectedCountryName = $selectedCountryCode ? ($countries[$selectedCountryCode] ?? null) : null;
                            $selectedCountryLabel = $selectedCountryName ? $selectedCountryName.' ('.Str::upper($selectedCountryCode).')' : '';
                        @endphp
                        <div x-data="{
                                open: false,
                                query: @js($selectedCountryLabel),
                                selected: @js($selectedCountryCode ?? ''),
                                countries: @js($countries),
                                select(code, label) { this.selected = code; this.query = label; this.open = false; },
                                clear() { this.selected = ''; this.query = ''; this.open = false; },
                                flagClass(code) { return code === '{{ \App\Support\Countries::INTERNATIONAL }}' ? 'un' : code; },
                             }" class="relative" @click.away="open = false">
                            <label for="country_code_query" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.edit.fields.country_code') }}
                            </label>
                            <input type="hidden" name="country_code" :value="selected">
                            <input id="country_code_query" type="text" x-model="query" @focus="open = true" autocomplete="off"
                                   placeholder="{{ __('team.edit.fields.country_code_search') }}"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <div x-show="open" x-cloak
                                 class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-[#050505] border border-border-subtle rounded-sm shadow-xl">
                                <div @click="clear()" class="px-4 py-2 text-xs text-gray-500 cursor-pointer hover:bg-white/5">
                                    {{ __('team.edit.fields.country_code_none') }}
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

                        <div class="sm:col-span-2">
                            <label for="bio" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.edit.fields.bio') }}
                            </label>
                            <textarea id="bio" name="bio" rows="3"
                                      class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('bio', $team->bio) }}</textarea>
                            @error('bio')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="liquipedia_link" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.edit.fields.liquipedia_link') }}
                            </label>
                            <input id="liquipedia_link" type="url" name="liquipedia_link" value="{{ old('liquipedia_link', $team->liquipedia_link) }}" placeholder="https://liquipedia.net/…"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('liquipedia_link')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.change_request.socials_section') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('team.change_request.socials_help') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @php $socials = $team->socials ?? []; @endphp
                        @foreach ($socialPlatforms as $platform)
                            <div>
                                <label for="social_{{ $platform }}" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                    {{ $platform === 'website' ? __('team.edit.fields.website') : ucfirst($platform) }}
                                </label>
                                <input id="social_{{ $platform }}" type="text" name="socials[{{ $platform }}]" value="{{ old('socials.'.$platform, $socials[$platform] ?? '') }}"
                                       placeholder="{{ $platform === 'discord' ? __('team.change_request.socials_placeholder_discord') : __('team.change_request.socials_placeholder_username') }}"
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                @error('socials.'.$platform)
                                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.change_request.logo_section') }}</h2>

                    <div class="flex items-center gap-4">
                        @if ($team->logo)
                            <img src="{{ $team->logo }}" alt="{{ __('team.change_request.logo_current') }}"
                                 class="w-16 h-16 object-contain border border-border-subtle rounded-sm bg-black/40 flex-shrink-0">
                        @endif

                        <div class="flex-grow">
                            <label for="logo" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.change_request.logo_label') }}
                            </label>
                            <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                                   class="w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-sm file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-gc-yellow file:text-black hover:file:opacity-90 file:cursor-pointer cursor-pointer">
                            <p class="text-xs text-gray-500 mt-2">{{ __('team.change_request.logo_help') }}</p>
                            @error('logo')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.change_request.roster_section') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('team.change_request.roster_intro') }}</p>

                    {{-- Current roster: add/remove entirely client-side (Alpine)
                         — nothing here submits until the whole page's form does. --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse ($roster as $entry)
                            <div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3" x-data="{ removed: false }">
                                <template x-if="!removed">
                                    <div class="space-y-3">
                                        <p class="text-sm font-semibold text-white truncate">{{ $entry->player_handle }}</p>

                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('team.roster.role') }}</label>
                                            <x-styled-select name="roster[{{ $entry->id }}][role]" :selected="old('roster.'.$entry->id.'.role', $entry->role)" :options="$roles" />
                                        </div>

                                        <div>
                                            <label for="roster_{{ $entry->id }}_joined_at" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('team.roster.joined_at') }}</label>
                                            <input id="roster_{{ $entry->id }}_joined_at" type="date" name="roster[{{ $entry->id }}][joined_at]"
                                                   value="{{ old('roster.'.$entry->id.'.joined_at', $entry->joined_at) }}"
                                                   class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                        </div>

                                        <button type="button" @click="removed = true"
                                                class="w-full font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                                            {{ __('team.roster.remove') }}
                                        </button>
                                    </div>
                                </template>

                                <template x-if="removed">
                                    <div class="flex items-center justify-between gap-2 py-2">
                                        <input type="hidden" name="roster[{{ $entry->id }}][removed]" value="1">
                                        <span class="text-xs text-gray-500 line-through truncate">{{ $entry->player_handle }}</span>
                                        <button type="button" @click="removed = false"
                                                class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-2 py-1 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                            {{ __('team.roster.undo') }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 sm:col-span-2">{{ __('team.roster.current_empty') }}</p>
                        @endforelse
                    </div>

                    {{-- New players: a fixed pool of pre-mounted entity-pickers,
                         each individually shown/hidden — a Livewire component
                         can't be instantiated dynamically from a plain Alpine
                         x-for, so "add" reveals the next free slot in this pool
                         (capped at MAX_NEW_PLAYERS) and each slot's own
                         "remove" hides just that one, in any order. --}}
                    @php $maxNewPlayers = \App\Http\Controllers\Auth\TeamChangeRequestController::MAX_NEW_PLAYERS; @endphp
                    <div class="pt-4 border-t border-border-subtle space-y-3" x-data="{
                            activeSlots: [],
                            addSlot() {
                                for (let i = 0; i < {{ $maxNewPlayers }}; i++) {
                                    if (! this.activeSlots.includes(i)) { this.activeSlots.push(i); break; }
                                }
                            },
                            removeSlot(i) { this.activeSlots = this.activeSlots.filter(slot => slot !== i); },
                         }">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @for ($i = 0; $i < $maxNewPlayers; $i++)
                                <template x-if="activeSlots.includes({{ $i }})">
                                    <div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3">
                                        <livewire:entity-picker type="player" :name="'new_players['.$i.'][player_id]'" :label="__('team.change_request.new_player_label')" :key="'new-player-'.$i" />

                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('team.roster.role') }}</label>
                                            <x-styled-select name="new_players[{{ $i }}][role]" :selected="old('new_players.'.$i.'.role', 'player')" :options="$roles" />
                                        </div>

                                        <div>
                                            <label for="new_players_{{ $i }}_joined_at" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('team.roster.joined_at') }}</label>
                                            <input id="new_players_{{ $i }}_joined_at" type="date" name="new_players[{{ $i }}][joined_at]"
                                                   value="{{ old('new_players.'.$i.'.joined_at', now()->format('Y-m-d')) }}"
                                                   class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                        </div>

                                        <button type="button" @click="removeSlot({{ $i }})"
                                                class="w-full font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                                            {{ __('team.roster.remove') }}
                                        </button>
                                    </div>
                                </template>
                            @endfor
                        </div>

                        @error('new_players')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror

                        <button type="button" @click="addSlot()" x-show="activeSlots.length < {{ $maxNewPlayers }}"
                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('team.change_request.roster_add') }}
                        </button>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.change_request.history_section') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('team.change_request.history_intro') }}</p>

                    @forelse ($rosterHistory as $row)
                        <div class="bg-[#050505] border border-border-subtle rounded-sm p-4 space-y-3">
                            <p class="text-sm font-semibold text-white">{{ $row->player_handle }}</p>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                        {{ __('team.roster.role') }}
                                    </label>
                                    <x-styled-select name="history[{{ $row->id }}][role]" :options="$roles" :selected="old('history.'.$row->id.'.role', $row->role)" />
                                </div>

                                <div>
                                    <label for="history_{{ $row->id }}_joined_at" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                        {{ __('team.roster.joined_at') }}
                                    </label>
                                    <input id="history_{{ $row->id }}_joined_at" type="date" name="history[{{ $row->id }}][joined_at]"
                                           value="{{ old('history.'.$row->id.'.joined_at', $row->joined_at) }}"
                                           class="bg-black/40 border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark] w-full">
                                    @error('history.'.$row->id.'.joined_at')
                                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="history_{{ $row->id }}_left_at" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                        {{ __('team.change_request.history_left_at') }}
                                    </label>
                                    <input id="history_{{ $row->id }}_left_at" type="date" name="history[{{ $row->id }}][left_at]"
                                           value="{{ old('history.'.$row->id.'.left_at', $row->left_at) }}"
                                           class="bg-black/40 border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark] w-full">
                                    @error('history.'.$row->id.'.left_at')
                                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">{{ __('team.change_request.history_empty') }}</p>
                    @endforelse
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.change_request.note_section') }}</h2>

                    <textarea id="note" name="note" rows="3" maxlength="1000"
                              placeholder="{{ __('team.change_request.note_placeholder') }}"
                              class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                    {{ __('team.change_request.submit') }}
                </button>
            </form>
        </section>
    </div>
@endsection
