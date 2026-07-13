{{--
    GC-Stats — News article grid card component

    Larger card used in 3-column grids (author/media pages). Shows a cover
    banner, lang badge, title, excerpt and author · media · date footer.

    Props:
      $news — App\Models\News  (with author and media eager-loaded)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['news'])

@php
    $url         = route('news.show', $news->slug);
    $authorName  = $news->author?->name ?? 'GC-Stats';
    $authorLogo  = $news->author?->currentLogo ? asset('storage/authors/' . $news->author->currentLogo->id . '/200x200.webp') : null;
    $authorSlug  = $news->author?->slug;
    $publisherName = $news->publisher?->name;
    $publisherSlug = $news->publisher?->slug;
    $date        = $news->published_at?->translatedFormat('d M Y') ?? '';
    $excerpt     = $news->excerpt ?? '';
@endphp

<a href="{{ $url }}"
   class="group flex flex-col rounded-xl bg-white/[0.02] border border-white/[0.05] hover:border-white/10 hover:bg-[#111] overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_32px_rgba(0,0,0,0.5)]"
   aria-label="{{ $news->title }}">

    {{-- Banner --}}
    <div class="relative h-40 w-full overflow-hidden shrink-0">
        @if($news->image_cover)
            <img src="{{ $news->image_cover }}" alt=""
                 class="w-full h-full object-cover opacity-50 group-hover:opacity-70 group-hover:scale-105 transition-all duration-500">
            <div class="absolute inset-0" style="background: linear-gradient(to bottom, transparent 40%, #111 100%)"></div>
        @else
            <div class="w-full h-full"
                 style="background: linear-gradient(135deg, #181200 0%, #0e0e0e 50%, #0b0b0b 100%)">
                <div class="absolute inset-0"
                     style="background: radial-gradient(ellipse at 25% 60%, rgba(255,213,0,0.1) 0%, transparent 65%)"></div>
                <div class="absolute inset-0 flex items-center justify-center opacity-[0.06]">
                    <svg class="w-20 h-20 text-[var(--brand-yellow)]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6"/>
                    </svg>
                </div>
            </div>
        @endif

        {{-- Lang badge --}}
        <div class="absolute top-2.5 right-2.5">
            <span class="px-1.5 py-0.5 rounded bg-black/70 backdrop-blur-sm text-[7px] font-black uppercase tracking-widest text-gray-400">
                {{ strtoupper($news->lang) }}
            </span>
        </div>

        {{-- Featured star --}}
        @if($news->is_featured)
            <div class="absolute top-2.5 left-2.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-[var(--brand-yellow)] text-black text-[7px] font-black uppercase tracking-[0.2em]">
                    ★ {{ __('news.featured') }}
                </span>
            </div>
        @endif
    </div>

    {{-- Body --}}
    <div class="flex flex-col flex-1 px-4 py-4">

        <h3 class="text-[13px] font-black uppercase tracking-tight text-white/80 group-hover:text-white transition-colors leading-snug mb-2 line-clamp-2">
            {{ $news->title }}
        </h3>

        @if($excerpt)
            <p class="text-[11px] text-gray-500 leading-relaxed line-clamp-3 group-hover:text-gray-400 transition-colors flex-1 mb-4">
                {{ $excerpt }}
            </p>
        @else
            <div class="flex-1"></div>
        @endif

        {{-- Footer: author · media · date --}}
        <div class="flex items-center gap-2 pt-3 border-t border-white/[0.05] min-w-0">

            {{-- Author avatar --}}
            @if($authorLogo)
                <img src="{{ $authorLogo }}" alt="{{ $authorName }}"
                     class="w-5 h-5 rounded-full object-cover shrink-0 opacity-80">
            @else
                <div class="w-5 h-5 rounded-full bg-[var(--brand-yellow)]/15 shrink-0 flex items-center justify-center">
                    <span class="text-[7px] font-black text-[var(--brand-yellow)]">{{ strtoupper(substr($authorName, 0, 1)) }}</span>
                </div>
            @endif

            <span class="text-[9px] font-bold uppercase tracking-widest text-gray-500 group-hover:text-gray-400 transition-colors truncate">
                {{ $authorName }}
            </span>

            @if($publisherName)
                <span class="text-white/10 shrink-0">·</span>
                <span class="text-[9px] font-bold uppercase tracking-widest text-gray-600 truncate shrink-0">{{ $publisherName }}</span>
            @endif

            <span class="text-white/10 shrink-0 ml-auto">·</span>
            <span class="text-[9px] font-bold text-gray-700 uppercase tabular-nums shrink-0">{{ $date }}</span>
        </div>
    </div>

</a>
