{{--
    GC-Stats — User avatar (Gravatar with initials fallback)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['user'])

<div {{ $attributes->class(['relative overflow-hidden shrink-0 flex items-center justify-center font-black uppercase text-white']) }}>
    <span>{{ $user->initials() }}</span>
    <img
        src="{{ $user->gravatarUrl() }}"
        alt=""
        loading="lazy"
        class="absolute inset-0 w-full h-full object-cover"
        onerror="this.style.display='none'"
    >
</div>
