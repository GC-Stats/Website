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

    <div>
        <label for="country_code" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
            {{ __('team.edit.fields.country_code') }}
        </label>
        <input id="country_code" type="text" name="country_code" value="{{ old('country_code', $team->country_code) }}" maxlength="5"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
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
