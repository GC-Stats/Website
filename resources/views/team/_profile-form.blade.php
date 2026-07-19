{{--
    GC-Stats — Team profile form fields

    Shared partial: the field markup only, no <form>/submit button — the
    including page owns those, so this same partial can be dropped into
    both the team-owner-facing page and (later) an admin-panel wrapper
    around the same App\Services\TeamProfileService update action.
    Expects $team.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php $socials = $team->socials ?? []; @endphp

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

    <div>
        <label for="liquipedia_link" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('team.edit.fields.liquipedia_link') }}
        </label>
        <input id="liquipedia_link" type="url" name="liquipedia_link" value="{{ old('liquipedia_link', $team->liquipedia_link) }}" placeholder="https://liquipedia.net/…"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('liquipedia_link')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="vlr_id" class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('team.edit.fields.vlr_id') }}
            <span class="group relative inline-flex" tabindex="0">
                @svg('fas-circle-info', 'w-3 h-3 text-gray-600 hover:text-gray-400 transition-colors cursor-help', ['aria-hidden' => 'true'])
                <span class="sr-only">{{ __('team.edit.fields.vlr_id_info') }}</span>
                <span role="tooltip"
                      class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 normal-case tracking-normal font-normal text-[11px] text-gray-300 bg-[#0a0a0a] border border-border-subtle rounded-sm px-3 py-2 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity z-10">
                    {{ __('team.edit.fields.vlr_id_info') }}
                </span>
            </span>
        </label>
        <input id="vlr_id" type="number" name="vlr_id" value="{{ old('vlr_id', $team->vlr_id) }}"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('vlr_id')
            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="pt-4 border-t border-border-subtle space-y-3">
    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('team.edit.fields.socials') }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach (['twitter', 'twitch', 'instagram', 'youtube', 'tiktok', 'discord', 'website'] as $platform)
            <div>
                <label for="social_{{ $platform }}" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ $platform === 'website' ? __('team.edit.fields.website') : ucfirst($platform) }}
                </label>
                <input id="social_{{ $platform }}" type="text" name="socials[{{ $platform }}]" value="{{ old('socials.'.$platform, $socials[$platform] ?? '') }}"
                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            </div>
        @endforeach
    </div>
</div>
