{{--
    GC-Stats — Stream channel create/edit form fields (shared content)

    Shared by resources/views/streams/_create.blade.php and _edit.blade.php,
    used from both admin/streams/{create,edit} (site-admin, extends
    admin.layout) and organization/dashboard/streams/{create,edit} (extends
    organization.layout). Expects $channel (null when creating), $platforms,
    $countries (for the language picker, same list as App\Support\Countries
    used by Player/Team::country_code), $organizations (already restricted
    to the ones this user may pick from — see
    Admin\StreamChannelController::formData()) and $organization (nullable —
    bound only in dashboard mode, where attribution is fixed rather than
    picked).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $selectedLanguageCode = Str::lower(old('language_code', $channel->language_code ?? '') ?? '') ?: null;
    $selectedLanguageName = $selectedLanguageCode ? ($countries[$selectedLanguageCode] ?? null) : null;
    $selectedLanguageLabel = $selectedLanguageName ? $selectedLanguageName.' ('.Str::upper($selectedLanguageCode).')' : '';
@endphp

<div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4 mb-6">
    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.streams.title') }}</h2>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.streams.fields.name') }}</span>
        <input type="text" name="name" value="{{ old('name', $channel->name ?? '') }}" required maxlength="255"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('name')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.streams.fields.platform') }}</span>
        <x-styled-select name="platform" :selected="old('platform', $channel->platform ?? '')"
            :options="collect($platforms)->mapWithKeys(fn ($platformOption) => [$platformOption => ucfirst($platformOption)])" />
        @error('platform')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.streams.fields.url') }}</span>
        <input type="url" name="url" value="{{ old('url', $channel->url ?? '') }}" required maxlength="2048" placeholder="https://…"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        @error('url')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </label>

    <div x-data="{
            open: false,
            query: @js($selectedLanguageLabel),
            selected: @js($selectedLanguageCode ?? ''),
            countries: @js($countries),
            select(code, label) { this.selected = code; this.query = label; this.open = false; },
            flagClass(code) { return code === '{{ \App\Support\Countries::INTERNATIONAL }}' ? 'un' : code; },
         }" class="relative" @click.away="open = false">
        <label for="language_code_query" class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">
            {{ __('admin.streams.fields.language_code') }}
        </label>
        <input type="hidden" name="language_code" :value="selected">
        <input id="language_code_query" type="text" x-model="query" @focus="open = true" autocomplete="off" required
               placeholder="{{ __('admin.streams.fields.language_code_search') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        <div x-show="open" x-cloak
             class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-[#0a0a0a] border border-white/10 rounded-lg shadow-xl">
            <template x-for="[code, name] in Object.entries(countries)" :key="code">
                <div x-show="query === '' || (name + ' ' + code).toLowerCase().includes(query.toLowerCase())"
                     @click="select(code, name + ' (' + code.toUpperCase() + ')')"
                     class="flex items-center gap-2 px-4 py-2 text-sm text-white cursor-pointer hover:bg-white/5">
                    <span class="fi shadow-sm flex-shrink-0" :class="'fi-' + flagClass(code)"></span>
                    <span x-text="name + ' (' + code.toUpperCase() + ')'"></span>
                </div>
            </template>
        </div>
        @error('language_code')
            <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
        @enderror
    </div>

    @if ($organization)
        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.streams.fields.organization') }}</span>
            <input type="hidden" name="organization_id" value="{{ $organization->id }}">
            <div class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-gray-300">
                {{ $organization->name }}
            </div>
        </label>
    @elseif (! $restricted)
        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.streams.fields.organization') }}</span>
            <x-styled-select name="organization_id" :selected="old('organization_id', $channel->organization_id ?? '')"
                :options="collect(['' => __('admin.streams.fields.organization_none')])->union($organizations->mapWithKeys(fn ($organizationOption) => [$organizationOption->id => $organizationOption->name]))" />
            @error('organization_id')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </label>
    @else
        <input type="hidden" name="organization_id" value="{{ $organizations->first()?->id }}">
    @endif

    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $channel->is_active ?? true))
               class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
        <span class="text-sm text-gray-300">{{ __('admin.streams.fields.is_active') }}</span>
    </label>
</div>
