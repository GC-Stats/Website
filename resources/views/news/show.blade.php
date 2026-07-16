{{--
    GC-Stats — News article page

    Full-width article layout: hero, wide prose body, related entities,
    then author + media credit strip at the bottom.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', $title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($excerpt ?? $content ?? ''), 160))
@section('canonical', route('news.show', request()->route('slug')))
@section('og_type', 'article')
@section('og_image', $imageCover ?: asset('web-app-manifest-512x512.png'))

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $title,
    'image' => $imageCover ? [$imageCover] : [],
    'datePublished' => $date,
    'author' => [
        '@type' => 'Organization',
        'name' => $author['name'] ?? config('app.name'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $publisher['name'] ?? config('app.name'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')

@php
    $authorName      = $author['name'] ?? 'GC-Stats';
    $authorLogo      = $author['logo'] ?? null;
    $authorSlug      = $author['slug'] ?? null;
    $authorBio       = $author['bio'] ?? null;
    $publisherName   = $publisher['name'] ?? null;
    $publisherLogo   = $publisher['logo'] ?? null;
    $publisherSlug   = $publisher['slug'] ?? null;
    $socialConfig = [
        'twitter'   => ['url' => 'https://x.com/',        'icon' => 'fab-x-twitter'],
        'instagram' => ['url' => 'https://instagram.com/', 'icon' => 'fab-instagram'],
        'twitch'    => ['url' => 'https://twitch.tv/',     'icon' => 'fab-twitch'],
        'youtube'   => ['url' => 'https://youtube.com/@',  'icon' => 'fab-youtube'],
        'discord'   => ['url' => '',                       'icon' => 'fab-discord'],
        'website'   => ['url' => '',                       'icon' => 'fas-globe'],
    ];

    $authorSocials    = $author['socials'] ?? [];
    $publisherSocials = $publisher['socials'] ?? [];
    $publisherWebsite = $publisherSocials['website'] ?? null;
@endphp

<div class="max-w-4xl mx-auto">

    {{-- ── Hero ───────────────────────────────────────────────────────── --}}
    <div class="relative rounded-2xl overflow-hidden mb-8 border border-white/[0.06]">
        @if($imageCover)
            <img src="{{ $imageCover }}" alt=""
                 class="w-full h-64 md:h-80 object-cover opacity-50">
            <div class="absolute inset-0"
                 style="background: linear-gradient(to bottom, transparent 20%, #0b0b0b 100%)"></div>
        @else
            <div class="w-full h-36 md:h-48"
                 style="background: linear-gradient(135deg, #1a1400 0%, #0d0d0d 50%, #0b0b0b 100%)">
                <div class="absolute inset-0"
                     style="background: radial-gradient(ellipse at 20% 50%, rgba(255,213,0,0.12) 0%, transparent 60%)"></div>
            </div>
        @endif

        <div class="absolute bottom-0 left-0 right-0 px-6 pb-6 pt-12">
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="text-[7px] font-black uppercase tracking-[0.25em] px-2 py-0.5 rounded bg-black/60 backdrop-blur-sm text-gray-400 border border-white/10">
                    {{ strtoupper($lang) }}
                </span>
                @if($publisherName)
                    <a href="{{ route('news.publisher', $publisherSlug) }}"
                       class="inline-flex items-center gap-1.5 text-[7px] font-black uppercase tracking-[0.2em] px-2 py-0.5 rounded bg-black/60 backdrop-blur-sm text-gray-300 border border-white/10 hover:border-[var(--brand-yellow)]/40 hover:text-white transition-all">
                        @if($publisherLogo)
                            <img src="{{ $publisherLogo }}" alt="" class="h-2.5 w-auto object-contain">
                        @endif
                        {{ $publisherName }}
                    </a>
                @endif
                <span class="text-[7px] font-bold uppercase tracking-widest text-gray-600">{{ $date }}</span>
            </div>

            <h1 class="text-xl md:text-2xl lg:text-3xl font-black uppercase tracking-tight text-white leading-tight">
                {{ $title }}
            </h1>
        </div>
    </div>

    {{-- ── Excerpt ─────────────────────────────────────────────────────── --}}
    @if($excerpt)
        <p class="text-sm md:text-base text-gray-400 leading-relaxed font-medium mb-8 pb-8 border-b border-white/[0.06] italic">
            {{ $excerpt }}
        </p>
    @endif

    {{-- ── Article body ────────────────────────────────────────────────── --}}
    <article class="news-content prose prose-invert max-w-none mb-10
                    prose-headings:font-black
                    prose-p:text-gray-300 prose-p:leading-relaxed prose-p:text-[15px]
                    prose-a:text-[var(--brand-yellow)]
                    prose-strong:text-white prose-strong:font-black
                    prose-ul:text-gray-300 prose-ol:text-gray-300
                    prose-li:text-[15px] prose-li:leading-relaxed
                    prose-img:rounded-xl prose-img:border prose-img:border-white/10 prose-img:w-full
                    prose-blockquote:border-l-2 prose-blockquote:border-[var(--brand-yellow)] prose-blockquote:pl-4 prose-blockquote:text-gray-400 prose-blockquote:not-italic prose-blockquote:bg-white/[0.02] prose-blockquote:rounded-r-lg prose-blockquote:py-1
                    prose-code:text-[var(--brand-yellow)] prose-code:bg-white/5 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm prose-code:font-mono prose-code:before:content-none prose-code:after:content-none
                    prose-pre:bg-white/[0.03] prose-pre:border prose-pre:border-white/10 prose-pre:rounded-xl">
        {!! $content !!}
    </article>

    {{-- ── Related entities ────────────────────────────────────────────── --}}
    @if(!empty($players) || !empty($teams) || !empty($tournaments))
        <div class="mb-10 pt-6 border-t border-white/[0.06]">
            <span class="text-[8px] font-black uppercase tracking-[0.3em] text-gray-600 mb-3 block">{{ __('news.related') }}</span>
            <div class="flex flex-wrap gap-2">
                @foreach($players as $player)
                    <a href="{{ route('players.show', [$player['id'], str($player['handle'] ?? '')->slug()]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/[0.03] border border-white/[0.06] hover:border-[var(--brand-yellow)]/30 hover:bg-white/[0.06] transition-all text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-white">
                        {{ $player['handle'] }}
                    </a>
                @endforeach
                @foreach($teams as $team)
                    <a href="{{ route('teams.show', [$team['id'], str($team['name'] ?? '')->slug()]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/[0.03] border border-white/[0.06] hover:border-[var(--brand-yellow)]/30 hover:bg-white/[0.06] transition-all text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-white">
                        {{ $team['name'] }}
                    </a>
                @endforeach
                @foreach($tournaments as $tournament)
                    <a href="{{ route('tournaments.show', [$tournament['id'], str($tournament['name'] ?? '')->slug()]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/[0.03] border border-white/[0.06] hover:border-[var(--brand-yellow)]/30 hover:bg-white/[0.06] transition-all text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-white">
                        {{ $tournament['name'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Author + Media strip ────────────────────────────────────────── --}}
    <div class="border-t border-white/[0.06] pt-8 grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Author --}}
        @if($author)
            <div class="flex items-start gap-4 bg-white/[0.02] border border-white/[0.06] rounded-xl p-4">
                @if($authorLogo)
                    <img src="{{ $authorLogo }}" alt="{{ $authorName }}"
                         class="w-12 h-12 rounded-lg object-cover border border-white/10 shrink-0">
                @else
                    <div class="w-12 h-12 rounded-lg bg-[var(--brand-yellow)]/10 border border-[var(--brand-yellow)]/20 flex items-center justify-center shrink-0">
                        <span class="text-lg font-black text-[var(--brand-yellow)]">{{ strtoupper(substr($authorName, 0, 1)) }}</span>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <span class="text-[8px] font-black uppercase tracking-[0.25em] text-gray-600 block mb-1">{{ __('news.author') }}</span>
                    <a href="{{ route('news.author', $authorSlug) }}"
                       class="text-[13px] font-black uppercase tracking-tight text-white hover:text-[var(--brand-yellow)] transition-colors block truncate mb-1">
                        {{ $authorName }}
                    </a>
                    @if($authorBio)
                        <p class="text-[10px] text-gray-500 leading-relaxed line-clamp-2 mb-2">{{ $authorBio }}</p>
                    @endif
                    @if(!empty($authorSocials))
                        <div class="flex items-center gap-1 flex-wrap">
                            @foreach($authorSocials as $platform => $value)
                                @if($value && isset($socialConfig[$platform]))
                                    @php $cfg = $socialConfig[$platform]; @endphp
                                    <a href="{{ $cfg['url'] . $value }}" target="_blank" rel="noopener noreferrer"
                                       aria-label="{{ ucfirst($platform) }}"
                                       class="w-6 h-6 flex items-center justify-center rounded bg-white/[0.04] border border-white/[0.06] text-gray-500 hover:text-[var(--brand-yellow)] hover:border-[var(--brand-yellow)]/30 transition-all">
                                        @svg($cfg['icon'], 'w-3 h-3', ['aria-hidden' => 'true'])
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Publisher --}}
        @if($publisher)
            <div class="flex items-start gap-4 bg-white/[0.02] border border-white/[0.06] rounded-xl p-4">
                @if($publisherLogo)
                    <div class="w-12 h-12 rounded-lg bg-white/[0.04] border border-white/10 flex items-center justify-center p-2 shrink-0">
                        <img src="{{ $publisherLogo }}" alt="{{ $publisherName }}" class="w-full h-full object-contain">
                    </div>
                @else
                    <div class="w-12 h-12 rounded-lg bg-[var(--brand-yellow)]/10 border border-[var(--brand-yellow)]/20 flex items-center justify-center shrink-0">
                        @svg('fas-newspaper', 'w-5 h-5 text-[var(--brand-yellow)]/50', ['aria-hidden' => 'true'])
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <span class="text-[8px] font-black uppercase tracking-[0.25em] text-gray-600 block mb-1">{{ __('news.publisher') }}</span>
                    <a href="{{ route('news.publisher', $publisherSlug) }}"
                       class="text-[13px] font-black uppercase tracking-tight text-white hover:text-[var(--brand-yellow)] transition-colors block truncate mb-1">
                        {{ $publisherName }}
                    </a>
                    @if($publisherWebsite)
                        <a href="{{ $publisherWebsite }}" target="_blank" rel="noopener noreferrer"
                           class="text-[10px] text-gray-500 hover:text-gray-300 transition-colors truncate block mb-2">
                            {{ parse_url($publisherWebsite, PHP_URL_HOST) }}
                        </a>
                    @endif
                    @if(!empty($publisherSocials))
                        <div class="flex items-center gap-1 flex-wrap">
                            @foreach($publisherSocials as $platform => $value)
                                @if($value && isset($socialConfig[$platform]) && $platform !== 'website')
                                    @php $cfg = $socialConfig[$platform]; @endphp
                                    <a href="{{ $cfg['url'] . $value }}" target="_blank" rel="noopener noreferrer"
                                       aria-label="{{ ucfirst($platform) }}"
                                       class="w-6 h-6 flex items-center justify-center rounded bg-white/[0.04] border border-white/[0.06] text-gray-500 hover:text-[var(--brand-yellow)] hover:border-[var(--brand-yellow)]/30 transition-all">
                                        @svg($cfg['icon'], 'w-3 h-3', ['aria-hidden' => 'true'])
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>

</div>

@endsection
