{{--
    GC-Stats — Agent icon component

    Renders a plain agent avatar. Role-colored glows (when used) are applied
    by the containing chip, not here — see the pick rate rows on the maps
    pages, which glow the whole chip rather than just the icon.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['agent', 'size' => 'w-6 h-6'])

@php
    $slug = \App\Helpers\AgentRoles::slug($agent);
@endphp

<img src="/storage/agents/{{ $slug }}.webp"
     class="{{ $size }} rounded-full border border-gray-900 bg-bg-main shrink-0"
     alt="{{ $agent }}" title="{{ $agent }}" loading="lazy">
