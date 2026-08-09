{{--
    GC-Stats — News organization page

    Lists all published articles attributed to one Organization — the
    entity News is attributed to (see the 0122 migration's docblock).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', $organization['name'])
@section('canonical', route('news.organization', [$organization['id'], $organization['routeSlug']]))

@section('content')

@php
    $organizationSocials = is_string($organization['socials'] ?? null) ? json_decode($organization['socials'], true) : ($organization['socials'] ?? []);
    $organizationWebsite = $organizationSocials['website'] ?? null;
@endphp

<div class="block group mb-8">
    <div class="relative bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 group-hover:bg-white/[0.04] group-hover:shadow-[0_20px_40px_rgba(0,0,0,0.6)]">
        <div class="p-4 md:p-6 flex flex-col gap-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 w-full">

                <div class="flex items-center gap-4 md:gap-5 w-full">
                    <div class="relative flex-shrink-0">
                        @if($organization['logo'])
                            <div class="w-16 h-16 md:w-32 md:h-32 flex items-center justify-center border border-white/10 rounded-lg bg-black/40 p-3">
                                <img src="{{ $organization['logo'] }}" alt="{{ $organization['name'] }}"
                                     class="w-full h-full object-contain">
                            </div>
                        @else
                            <div class="w-16 h-16 md:w-32 md:h-32 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10">
                                @svg('fas-building', 'w-7 h-7 md:w-12 md:h-12 text-[var(--brand-yellow)]/40', ['aria-hidden' => 'true'])
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col justify-center min-w-0 flex-grow">
                        <p class="text-gray-500 text-[10px] font-black uppercase tracking-[0.25em] mb-1">
                            {{ $articles->total() }} {{ __('news.articles') }}
                        </p>

                        <h1 class="text-2xl md:text-3xl font-black text-white uppercase tracking-tight leading-none group-hover:text-[var(--brand-yellow)] transition-colors truncate mb-2">
                            {{ $organization['name'] }}
                        </h1>

                        @if($organizationWebsite)
                            <a href="{{ $organizationWebsite }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 text-[11px] md:text-[13px] font-bold text-gray-400 hover:text-gray-100 transition-colors">
                                @svg('fas-globe', 'w-3 h-3 shrink-0', ['aria-hidden' => 'true'])
                                {{ parse_url($organizationWebsite, PHP_URL_HOST) }}
                            </a>
                        @endif

                        <a href="{{ route('organizations.show', [$organization['id'], $organization['routeSlug']]) }}"
                           class="inline-flex items-center gap-1.5 text-[11px] font-bold text-gc-yellow hover:text-white transition-colors mt-1">
                            {{ __('organization.title.index', ['organization' => $organization['name']]) }} &rarr;
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap md:flex-col gap-2 w-full md:w-auto md:min-w-[180px] md:pl-6">
                    @php
                        $socialConfig = collect([
                            'twitter'   => ['url' => 'https://x.com/', 'icon' => 'fab-x-twitter'],
                            'twitch'    => ['url' => 'https://twitch.tv/', 'icon' => 'fab-twitch'],
                            'tiktok'    => ['url' => 'https://tiktok.com/@', 'icon' => 'fab-tiktok'],
                            'instagram' => ['url' => 'https://instagram.com/', 'icon' => 'fab-instagram'],
                            'youtube'   => ['url' => 'https://youtube.com/@', 'icon' => 'fab-youtube'],
                            'discord'   => ['url' => '#', 'icon' => 'fab-discord'],
                            'website'   => ['url' => '', 'icon' => 'fas-globe'],
                        ]);
                    @endphp

                    @if(!empty($organizationSocials))
                        <div class="flex flex-col gap-1 min-w-[150px] justify-center border-t md:border-t-0 md:border-l border-border-subtle pt-4 md:pt-0 md:pl-6">
                            @foreach($organizationSocials as $platform => $username)
                                @if($username && is_scalar($username) && $socialConfig->has($platform))
                                    @php $cfg = $socialConfig->get($platform); @endphp
                                    <a href="{{ $cfg['url'] . $username }}" target="_blank" rel="noopener noreferrer"
                                       aria-label="{{ ucfirst($platform) }}: {{ $username }}"
                                       class="flex items-center gap-2 text-gray-400 hover:text-gc-yellow transition-colors group/socials py-0.5">
                                        <div class="w-6 h-6 flex items-center justify-center bg-bg-body rounded-sm group-hover/socials:bg-gc-yellow/10 flex-shrink-0">
                                            @svg($cfg['icon'], 'w-[11px] h-[11px] inline-block text-[11px]', ['aria-hidden' => 'true'])
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider truncate max-w-[100px]">
                                            @if($platform == 'website')
                                                Website
                                            @else
                                                {{ $username }}
                                            @endif
                                        </span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mb-6">
    <span class="text-[8px] font-black uppercase tracking-[0.3em] text-gray-600">{{ __('news.articles') }}</span>
    <div class="h-px flex-grow bg-white/5"></div>
</div>

@if($articles->isEmpty())
    <div class="text-center py-20">
        <p class="text-[11px] font-bold uppercase tracking-widest text-gray-600">{{ __('news.no_articles') }}</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
        @foreach($articles as $article)
            <x-public.news.article :news="$article" />
        @endforeach
    </div>

    @if($articles->hasPages())
        <div class="flex justify-center">
            {{ $articles->links() }}
        </div>
    @endif
@endif

@endsection
