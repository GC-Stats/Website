{{--
    GC-Stats — Pagination view (Tailwind)

    Customized copy of Laravel's default Tailwind pagination view, styled
    to match GC-Stats' dark theme.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between py-6">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="px-6 py-2 text-[10px] font-black text-gray-700 bg-white/5 border border-white/5 rounded-lg cursor-not-allowed uppercase tracking-widest">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-6 py-2 text-[10px] font-black text-white bg-white/5 border border-white/10 rounded-lg hover:border-[var(--brand-yellow)] hover:text-[var(--brand-yellow)] transition-all uppercase tracking-widest">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-6 py-2 text-[10px] font-black text-white bg-white/5 border border-white/10 rounded-lg hover:border-[var(--brand-yellow)] hover:text-[var(--brand-yellow)] transition-all uppercase tracking-widest">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="px-6 py-2 text-[10px] font-black text-gray-700 bg-white/5 border border-white/5 rounded-lg cursor-not-allowed uppercase tracking-widest">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-[10px] text-gray-500 uppercase tracking-[0.2em] font-bold">
                    <span class="text-white/20 mr-1">//</span>
                    {!! __('Showing') !!}
                    <span class="text-[var(--brand-yellow)]">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="text-[var(--brand-yellow)]">{{ $paginator->lastItem() }}</span>
                    {!! __('of') !!}
                    <span class="text-white">{{ $paginator->total() }}</span>
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex gap-2">
                    @if ($paginator->onFirstPage())
                        <span class="relative inline-flex items-center px-3 py-2 text-gray-700 bg-white/[0.02] border border-white/5 rounded-lg cursor-not-allowed" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="relative inline-flex items-center px-3 py-2 text-gray-400 bg-white/5 border border-white/10 rounded-lg hover:text-[var(--brand-yellow)] hover:border-[var(--brand-yellow)]/50 transition-all group">
                            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="relative inline-flex items-center px-4 py-2 text-[11px] font-black text-gray-700 bg-transparent uppercase">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="relative inline-flex items-center px-4 py-2 text-[11px] font-black text-black bg-[var(--brand-yellow)] border border-[var(--brand-yellow)] rounded-lg shadow-[0_0_15px_rgba(255,215,0,0.2)]" aria-current="page" aria-label="{{ __('pagination.page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" aria-label="{{ __('pagination.go_to_page', ['page' => $page]) }}" class="relative inline-flex items-center px-4 py-2 text-[11px] font-black text-gray-500 bg-white/5 border border-white/10 rounded-lg hover:text-white hover:border-white/20 transition-all">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="relative inline-flex items-center px-3 py-2 text-gray-400 bg-white/5 border border-white/10 rounded-lg hover:text-[var(--brand-yellow)] hover:border-[var(--brand-yellow)]/50 transition-all group">
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </a>
                    @else
                        <span class="relative inline-flex items-center px-3 py-2 text-gray-700 bg-white/[0.02] border border-white/5 rounded-lg cursor-not-allowed" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
