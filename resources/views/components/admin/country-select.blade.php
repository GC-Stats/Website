{{--
    GC-Stats — Shared country picker

    Extracted from the Alpine country-search markup duplicated across
    team/_profile-form.blade.php, player/_profile-form.blade.php and the
    admin players "create" modal — same look/behaviour everywhere a
    country_code field is edited. Expects $countries (code => name map, see
    App\Support\Countries::list()).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'name' => 'country_code',
    'label' => null,
    'selected' => null,
    'countries' => [],
    'searchPlaceholder' => null,
    'noneLabel' => null,
    'id' => null,
])

@php
    $id = $id ?? ($name.'_query');
    $selectedCountryCode = \Illuminate\Support\Str::lower($selected ?? '') ?: null;
    $selectedCountryName = $selectedCountryCode ? ($countries[$selectedCountryCode] ?? null) : null;
    $selectedCountryLabel = $selectedCountryName ? $selectedCountryName.' ('.\Illuminate\Support\Str::upper($selectedCountryCode).')' : '';
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
    @if ($label)
        <label for="{{ $id }}" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ $label }}
        </label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="selected">
    <input id="{{ $id }}" type="text" x-model="query" @focus="open = true" autocomplete="off"
           placeholder="{{ $searchPlaceholder }}"
           {{ $attributes->merge(['class' => 'w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition']) }}>
    <div x-show="open" x-cloak
         class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-bg-card border border-white/10 rounded-lg shadow-xl">
        <div @click="clear()" class="px-4 py-2 text-xs text-gray-500 cursor-pointer hover:bg-white/5">
            {{ $noneLabel }}
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
    @error($name)
        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
    @enderror
</div>
