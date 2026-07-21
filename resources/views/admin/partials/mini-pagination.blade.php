{{--
    GC-Stats — Admin: minimal prev/next pagination

    A compact prev/next + "page / total" control for narrow dashboard
    widgets, where the full Tailwind pagination view (page numbers, item
    counts) would overflow a quarter-width column.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@if ($paginator->hasPages())
    <div class="flex items-center justify-between px-4 py-3 border-t border-white/5">
        @if ($paginator->onFirstPage())
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-700">&larr;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[var(--brand-yellow)] transition">&larr;</a>
        @endif

        <span class="text-[10px] text-gray-500">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[var(--brand-yellow)] transition">&rarr;</a>
        @else
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-700">&rarr;</span>
        @endif
    </div>
@endif
