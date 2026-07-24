{{--
    GC-Stats — Entity thumbnail

    Shared icon-slot renderer for entity-picker.blade.php: picks the right
    visual (photo/logo/emote image, gravatar+initials, or a country flag
    fallback for entities with neither) based on the serialized item's
    `image_kind` (see App\Services\SearchService::serializeEntity()). Kept
    as its own partial so the picker's three render sites (result row,
    selected chip, info modal header) stay in sync automatically.

    Teams/players always resolve to *some* image (a default placeholder
    when they have no real logo/photo — see Team/Player::resolveLogoUrl()),
    so the flag branch below only ever fires for kinds with no image
    concept at all (e.g. a user with no avatar). Callers that want a flag
    alongside the (possibly placeholder) logo — like entity-picker's
    selected chip — render their own, since this partial always shows the
    real image when there is one.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $kind = $item['image_kind'] ?? 'none';
    $image = $item['image'] ?? null;
    $country = $item['country_code'] ?? null;
    $initials = $item['initials'] ?? null;
@endphp

@if ($kind === 'avatar')
    <img src="{{ $image }}" alt="{{ $item['title'] }}" class="{{ $size }} object-cover"
         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
    <span class="{{ $size }} hidden items-center justify-center text-xs font-black text-gray-300 bg-white/10">
        {{ $initials ?? '?' }}
    </span>
@elseif ($kind === 'emote' && $image)
    <img src="{{ $image }}" alt="{{ $item['title'] }}" class="{{ $size }} object-contain p-1.5" loading="lazy">
@elseif (in_array($kind, ['photo', 'logo']) && $image)
    <img src="{{ $image }}" alt="{{ $item['title'] }}" class="{{ $size }} {{ $kind === 'photo' ? 'object-cover' : 'object-contain p-1.5' }}">
@elseif ($country)
    <span class="fi fi-{{ strtolower($country) }} fis rounded-lg shadow-sm w-full h-full" aria-label="{{ $country }}"></span>
@else
    <x-fas-circle-question class="w-1/2 h-1/2 text-gray-600" aria-hidden="true" />
@endif
