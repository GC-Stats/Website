{{--
    GC-Stats — Player: suggest an edit

    Dedicated page (not a modal) letting any authenticated user propose a
    correction to a player's profile — every proposed field goes through
    the moderated ChangeRequest queue, never applied directly. See
    App\Http\Controllers\Auth\PlayerChangeRequestController.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('player.change_request.title', ['player' => $player->handle]))

@php $playerParams = [$player->id, Str::routeSlug($player->handle, $player->id)]; @endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <div class="border-b border-border-subtle pb-6">
                <a href="{{ route('players.show', $playerParams) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-4">
                    &larr; {{ $player->handle }}
                </a>
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('player.change_request.title', ['player' => $player->handle]) }}</h1>
                <p class="text-sm text-gray-400 mt-2">{{ __('player.change_request.intro') }}</p>
            </div>

            @if (session('status') === 'change-request-submitted')
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __('player.change_request.submitted_status') }}
                </div>
            @endif

            @if ($errors->has('change_request'))
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3">
                    {{ $errors->first('change_request') }}
                </div>
            @endif

            <form method="POST" action="{{ route('players.change-requests.store', $player->id) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.change_request.profile_section') }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('player.edit.fields.first_name') }}
                            </label>
                            <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $player->first_name) }}"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('first_name')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('player.edit.fields.last_name') }}
                            </label>
                            <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $player->last_name) }}"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('last_name')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        @php
                            $selectedCountryCode = Str::lower(old('country_code', $player->country_code) ?? '') ?: null;
                            $selectedCountryName = $selectedCountryCode ? ($countries[$selectedCountryCode] ?? null) : null;
                            $selectedCountryLabel = $selectedCountryName ? $selectedCountryName.' ('.Str::upper($selectedCountryCode).')' : '';
                        @endphp
                        <div class="sm:col-span-2" x-data="{
                                open: false,
                                query: @js($selectedCountryLabel),
                                selected: @js($selectedCountryCode ?? ''),
                                countries: @js($countries),
                                select(code, label) { this.selected = code; this.query = label; this.open = false; },
                                clear() { this.selected = ''; this.query = ''; this.open = false; },
                                flagClass(code) { return code === '{{ \App\Support\Countries::INTERNATIONAL }}' ? 'un' : code; },
                             }" class="relative" @click.away="open = false">
                            <label for="country_code_query" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('player.edit.fields.country_code') }}
                            </label>
                            <input type="hidden" name="country_code" :value="selected">
                            <input id="country_code_query" type="text" x-model="query" @focus="open = true" autocomplete="off"
                                   placeholder="{{ __('player.edit.fields.country_code_search') }}"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <div x-show="open" x-cloak
                                 class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-[#050505] border border-border-subtle rounded-sm shadow-xl">
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

                        <div class="sm:col-span-2">
                            <label for="bio" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('player.edit.fields.bio') }}
                            </label>
                            <textarea id="bio" name="bio" rows="3"
                                      class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('bio', $player->bio) }}</textarea>
                            @error('bio')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.change_request.socials_section') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('player.change_request.socials_help') }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @php $socials = $player->socials ?? []; @endphp
                        @foreach ($socialPlatforms as $platform)
                            <div>
                                <label for="social_{{ $platform }}" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                    {{ ucfirst($platform) }}
                                </label>
                                <input id="social_{{ $platform }}" type="text" name="socials[{{ $platform }}]" value="{{ old('socials.'.$platform, $socials[$platform] ?? '') }}"
                                       placeholder="{{ $platform === 'discord' ? __('player.change_request.socials_placeholder_discord') : __('player.change_request.socials_placeholder_username') }}"
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                @error('socials.'.$platform)
                                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.change_request.photo_section') }}</h2>

                    <div class="flex items-center gap-4">
                        @if ($player->profile_photo)
                            <img src="{{ $player->profile_photo }}" alt="{{ __('player.change_request.photo_current') }}"
                                 class="w-16 h-16 object-contain border border-border-subtle rounded-sm bg-black/40 flex-shrink-0">
                        @endif

                        <div class="flex-grow">
                            <label for="photo" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('player.change_request.photo_label') }}
                            </label>
                            <input id="photo" type="file" name="photo" accept="image/png,image/jpeg,image/webp"
                                   class="w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-sm file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-gc-yellow file:text-black hover:file:opacity-90 file:cursor-pointer cursor-pointer">
                            <p class="text-xs text-gray-500 mt-2">{{ __('player.change_request.photo_help') }}</p>
                            @error('photo')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.change_request.team_section') }}</h2>
                    <p class="text-xs text-gray-500">
                        {{ $currentTeam ? __('player.change_request.current_team', ['team' => $currentTeam->name]) : __('player.change_request.no_current_team') }}
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-3">
                            <livewire:entity-picker type="team" name="team_id" :label="__('player.change_request.new_team_label')" :selected="old('team_id')" />
                            @error('team_id')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.roster.role') }}
                            </label>
                            <x-styled-select name="role" :options="$roles" :selected="old('role', 'player')" />
                        </div>

                        <div>
                            <label for="joined_at" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.roster.joined_at') }}
                            </label>
                            <input id="joined_at" type="date" name="joined_at" value="{{ old('joined_at', now()->format('Y-m-d')) }}"
                                   class="bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark] w-full">
                            @error('joined_at')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.change_request.history_section') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('player.change_request.history_intro') }}</p>

                    @forelse ($teamHistory as $row)
                        <div class="bg-[#050505] border border-border-subtle rounded-sm p-4 space-y-3">
                            <p class="text-sm font-semibold text-white">{{ $row->team_name }}</p>

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
                                        {{ __('player.change_request.history_left_at') }}
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
                        <p class="text-xs text-gray-500">{{ __('player.change_request.history_empty') }}</p>
                    @endforelse
                </div>

                @if (! $player->user_id)
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-3">
                        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.change_request.link_section') }}</h2>

                        <label class="flex items-start gap-2 text-sm text-gray-300">
                            <input type="checkbox" name="link_to_me" value="1" @checked(old('link_to_me'))
                                   class="mt-0.5 rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            {{ __('player.change_request.link_to_me_label') }}
                        </label>
                        <p class="text-xs text-gray-500">{{ __('player.change_request.link_to_me_note') }}</p>
                    </div>
                @endif

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.change_request.note_section') }}</h2>

                    <textarea id="note" name="note" rows="3" maxlength="1000"
                              placeholder="{{ __('player.change_request.note_placeholder') }}"
                              class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                    {{ __('player.change_request.submit') }}
                </button>
            </form>
        </section>
    </div>
@endsection
