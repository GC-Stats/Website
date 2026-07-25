{{--
    GC-Stats — Standard news card component

    Renders a regular news entry with a square icon (media logo or default),
    title, author · date, and a chevron. Used for all non-featured published news.

    Props:
      $news — array  (with author and publisher arrays)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['news'])

@php
    $url           = route('news.show', $news['slug']);
    $authorName    = $news['author']['name'] ?? 'GC-Stats';
    $authorAvatar  = $news['author']['logo'] ?? null;
    $publisherName = $news['publisher']['name'] ?? null;
    $publisherLogo = $news['publisher']['logo'] ?? null;
    $date          = $news['published_at'] ? \Carbon\Carbon::parse($news['published_at'])->translatedFormat('d M Y') : '';
@endphp

<a href="{{ $url }}"
   class="group flex items-center gap-3 rounded-xl bg-white/[0.02] hover:bg-[#111] border border-white/[0.04] hover:border-white/10 transition-all duration-300 px-3 py-3"
   aria-label="{{ $news['title'] }}">

    {{-- Square icon: media logo or default news icon --}}
    <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center border border-white/10 group-hover:border-[var(--brand-yellow)]/30 transition-colors overflow-hidden"
         style="background: rgba(255,213,0,0.05)">
        @if($publisherLogo)
            <img src="{{ $publisherLogo }}" alt="{{ $publisherName }}" class="w-6 h-6 object-contain opacity-60 group-hover:opacity-90 transition-opacity">
        @else
            <svg class="w-4 h-4 text-[var(--brand-yellow)]/50 group-hover:text-[var(--brand-yellow)]/80 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6"/>
            </svg>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <p class="text-[11px] font-black uppercase tracking-tight text-white/75 group-hover:text-white transition-colors leading-snug truncate mb-1.5">
            {{ $news['title'] }}
        </p>
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 min-w-0">
                {{-- Author avatar --}}
                @if($authorAvatar)
                    <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" class="w-3.5 h-3.5 rounded-full object-cover shrink-0 opacity-80">
                @else
                    <div class="w-3.5 h-3.5 rounded-full bg-[var(--brand-yellow)]/20 shrink-0 flex items-center justify-center">
                        <span class="text-[5px] font-black text-[var(--brand-yellow)]">{{ strtoupper(substr($authorName, 0, 1)) }}</span>
                    </div>
                @endif

                <span class="text-[8px] font-bold uppercase tracking-widest text-[var(--brand-yellow)] truncate">{{ $authorName }}</span>

                @if($publisherName)
                    <span class="text-white/10 shrink-0">·</span>
                    <span class="text-[8px] font-bold text-gray-600 uppercase truncate shrink-0">{{ $publisherName }}</span>
                @endif
            </div>

            @if($date)
                <span class="shrink-0 text-[8px] font-bold text-gray-700 uppercase tabular-nums">{{ $date }}</span>
            @endif
        </div>
    </div>

    {{-- Chevron --}}
    <svg class="shrink-0 w-3 h-3 text-gray-700 group-hover:text-[var(--brand-yellow)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
    </svg>

</a>
