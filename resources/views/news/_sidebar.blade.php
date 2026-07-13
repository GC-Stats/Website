{{--
    GC-Stats — News sidebar partial

    Shared news section used in player, team and homepage sidebars.
    Displays published news filtered by locale with a language notice.

    Variables expected:
      $news         — Collection<News>  (with author and media loaded)
      $sectionTitle — string            (optional, defaults to "News")
      $newsFeatured — News|null         (homepage only, omit elsewhere)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}

@php $sectionTitle = $sectionTitle ?? __('index.news'); @endphp

{{-- Section header --}}
<div class="flex items-center gap-2 mb-4">
    {{-- Language notice --}}
    <div x-data="{ open: false }"
         class="relative shrink-0"
         @mouseenter="open = true"
         @mouseleave="open = false"
         @focusin="open = true"
         @focusout="open = false">

        <button type="button"
                aria-label="{{ __('news.locale_info_label') }}"
                class="flex items-center gap-1 px-1.5 py-0.5 rounded border border-white/[0.08] bg-white/[0.02] hover:bg-white/[0.05] hover:border-white/20 transition-all">
            <svg class="w-2.5 h-2.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
            </svg>
            <span class="text-[7px] font-black uppercase tracking-widest text-gray-600 hover:text-gray-400">{{ strtoupper(app()->getLocale()) }}</span>
        </button>

        <div x-show="open"
             x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             role="tooltip"
             class="absolute left-0 top-7 z-50 w-56 rounded-xl bg-[#111] border border-white/10 shadow-[0_12px_32px_rgba(0,0,0,0.8)] p-3">
            <p class="text-[9px] text-gray-400 leading-relaxed">
                {{ __('news.locale_info') }}
            </p>
            <div class="absolute -top-1.5 left-2 w-3 h-3 bg-[#111] border-l border-t border-white/10 rotate-45"></div>
        </div>
    </div>

    <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ $sectionTitle }}</span>
    <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
</div>

{{-- Content --}}
<div class="space-y-3">
    @if(isset($newsFeatured) && $newsFeatured)
        <x-news.featured :news="$newsFeatured" />
    @endif

    @forelse($news as $item)
        @if(isset($item) && $item)
            @php $mobileLimit = (isset($newsFeatured) && $newsFeatured) ? 4 : 5; @endphp
            <div class="{{ $loop->index >= $mobileLimit ? 'hidden lg:block' : '' }}">
                <x-news.card :news="$item" />
            </div>
        @endif
    @empty
        @if(!isset($newsFeatured) || !$newsFeatured)
            <p class="text-[9px] font-bold uppercase tracking-widest text-gray-700 py-2">
                {{ __('news.no_articles') }}
            </p>
        @endif
    @endforelse
</div>

