{{--
    GC-Stats — Admin: sort arrow indicator

    Small up/down chevron pair used next to sortable table headers, styled
    to match the public tournament stats table. Expects an Alpine scope
    exposing `sortCol` / `sortAsc` (see GCS.sortableTable in app.js) and a
    `$col` variable naming this column.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<span class="flex flex-col opacity-20 group-hover:opacity-100 transition-opacity" :class="sortCol === '{{ $col }}' ? 'opacity-100' : ''">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 mb-0.5" :class="sortCol === '{{ $col }}' && sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 3l8 8h-16l8-8z" />
    </svg>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" :class="sortCol === '{{ $col }}' && !sortAsc ? 'text-gc-yellow' : 'text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 21l-8-8h16l-8 8z" />
    </svg>
</span>
