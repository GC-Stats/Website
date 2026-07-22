{{--
    GC-Stats — Admin: server-side sortable table header

    A <th> whose label links back to the same page with `sort`/`direction`
    query params set, preserving every other current query param (filters,
    etc.) via fullUrlWithQuery. Clicking re-requests the page so the
    database query re-sorts the *entire* result set, not just the rows
    currently rendered.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['col', 'sort', 'direction', 'class' => 'px-4 py-3'])

@php
    $isActive = $sort === $col;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $href = request()->fullUrlWithQuery(['sort' => $col, 'direction' => $nextDirection, 'page' => null]);
@endphp
<th {{ $attributes->merge(['class' => $class]) }}>
    <a href="{{ $href }}" class="group inline-flex items-center gap-1 hover:text-white transition cursor-pointer select-none">
        {{ $slot }}
        <span class="flex flex-col opacity-20 group-hover:opacity-100 transition-opacity {{ $isActive ? 'opacity-100' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 mb-0.5 {{ $isActive && $direction === 'asc' ? 'text-gc-yellow' : 'text-gray-500' }}" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3l8 8h-16l8-8z" />
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 {{ $isActive && $direction === 'desc' ? 'text-gc-yellow' : 'text-gray-500' }}" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 21l-8-8h16l-8 8z" />
            </svg>
        </span>
    </a>
</th>
