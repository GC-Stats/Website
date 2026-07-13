{{--
    GC-Stats — Featured news card component

    Renders the "À la une" news card with a banner image (or a gradient
    placeholder), used exclusively for news where is_featured = true.

    Props:
      $news — array  (with author and publisher arrays)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['news'])

@php
    $url           = route('news.show', $news['slug']);
    $hasCover      = !empty($news['image_cover']);
    $authorName    = $news['author']['name'] ?? 'GC-Stats';
    $authorAvatar  = $news['author']['logo'] ?? null;
    $publisherName = $news['publisher']['name'] ?? null;
    $date        = $news['published_at'] ? \Carbon\Carbon::parse($news['published_at'])->translatedFormat('d M Y') : '';
@endphp

<a href="{{ $url }}" class="group block" aria-label="{{ $news['title'] }}">
    <article class="relative rounded-xl overflow-hidden border border-[var(--brand-yellow)]/20 hover:border-[var(--brand-yellow)]/50 transition-all duration-300">

        {{-- Banner --}}
        <div class="relative h-28 w-full overflow-hidden">
            @if($hasCover)
                <img src="{{ $news['image_cover'] }}"
                     alt=""
                     class="w-full h-full object-cover opacity-60 group-hover:opacity-80 group-hover:scale-105 transition-all duration-500">
                <div class="absolute inset-0" style="background: linear-gradient(to bottom, transparent 30%, var(--bg-card) 100%)"></div>
            @else
                <div class="relative w-full h-full overflow-hidden" style="background: linear-gradient(135deg, var(--bg-card-hover) 0%, var(--bg-card) 55%, var(--bg-main) 100%)">
                    {{-- Soft brand glow on the left, where the banner would otherwise sit empty --}}
                    <div class="absolute inset-0" style="background: radial-gradient(ellipse 60% 80% at 12% 50%, rgba(228,174,34,0.14) 0%, transparent 70%)"></div>

                    {{-- Halftone dot texture across the whole banner --}}
                    <div class="absolute inset-0" style="
                        background-image: radial-gradient(rgba(228,174,34,0.55) 1px, transparent 1.5px), radial-gradient(rgba(228,174,34,0.55) 1px, transparent 1.5px);
                        background-size: 10px 10px;
                        background-position: 0 0, 5px 5px;
                        opacity: 0.12;
                    "></div>
                </div>
            @endif

            {{-- Featured badge --}}
            <div class="absolute top-2 left-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-[var(--brand-yellow)] text-black text-[7px] font-black uppercase tracking-[0.2em]">
                    ★ {{ __('news.featured') }}
                </span>
            </div>
        </div>

        {{-- Content --}}
        <div class="px-3 py-3 border-t" style="background: var(--bg-card); border-color: var(--color-border-subtle)">
            <h3 class="text-[11px] font-black uppercase tracking-tight leading-snug mb-2.5 group-hover:text-[var(--brand-yellow)] transition-colors line-clamp-2" style="color: var(--text-primary)">
                {{ $news['title'] }}
            </h3>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    {{-- Author avatar --}}
                    @if($authorAvatar)
                        <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" class="w-4 h-4 rounded-full object-cover shrink-0 opacity-80">
                    @else
                        <div class="w-4 h-4 rounded-full bg-[var(--brand-yellow)]/20 shrink-0 flex items-center justify-center">
                            <span class="text-[6px] font-black text-[var(--brand-yellow)]">{{ strtoupper(substr($authorName, 0, 1)) }}</span>
                        </div>
                    @endif

                    <span class="text-[8px] font-bold uppercase tracking-widest text-[var(--brand-yellow)] truncate">{{ $authorName }}</span>

                    @if($publisherName)
                        <span class="shrink-0" style="color: var(--color-border-subtle)">·</span>
                        <span class="text-[8px] font-bold uppercase truncate shrink-0" style="color: var(--text-muted)">{{ $publisherName }}</span>
                    @endif
                </div>

                <span class="shrink-0 text-[8px] font-bold uppercase tabular-nums" style="color: var(--text-muted)">{{ $date }}</span>
            </div>
        </div>

    </article>
</a>
