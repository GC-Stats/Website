{{--
    GC-Stats — Public user profile: News tab

    Replaces what used to be a standalone news.author page — an author's
    published articles now live on their linked user profile's News tab
    instead. Only reachable when the user has a linked News\Author profile
    — see UserProfileController::news().

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', $profileUser->name)

@php
    $socialConfig = collect([
        'twitter' => ['url' => 'https://x.com/', 'icon' => 'fab-x-twitter'],
        'instagram' => ['url' => 'https://instagram.com/', 'icon' => 'fab-instagram'],
        'twitch' => ['url' => 'https://twitch.tv/', 'icon' => 'fab-twitch'],
        'youtube' => ['url' => 'https://youtube.com/@', 'icon' => 'fab-youtube'],
        'discord' => ['url' => '', 'icon' => 'fab-discord'],
        'website' => ['url' => '', 'icon' => 'fas-globe'],
    ]);
    $authorSocials = $profileUser->newsAuthor->socials ?? [];
@endphp

@section('content')
    @include('users.header')

    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 space-y-6">
            @if ($profileUser->newsAuthor->bio || ! empty($authorSocials))
                <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-4">
                    @if ($profileUser->newsAuthor->bio)
                        <p class="text-sm text-gray-300 mb-3">{{ $profileUser->newsAuthor->bio }}</p>
                    @endif

                    @if (! empty($authorSocials))
                        <div class="flex flex-wrap gap-3">
                            @foreach ($authorSocials as $platform => $value)
                                @if ($value && $socialConfig->has($platform))
                                    @php $cfg = $socialConfig->get($platform); @endphp
                                    <a href="{{ $cfg['url'].$value }}" target="_blank" rel="noopener noreferrer"
                                       aria-label="{{ ucfirst($platform) }}: {{ $value }}"
                                       class="flex items-center gap-2 text-gray-400 hover:text-gc-yellow transition-colors">
                                        <div class="w-6 h-6 flex items-center justify-center bg-bg-body rounded-sm flex-shrink-0">
                                            @svg($cfg['icon'], 'w-[11px] h-[11px] inline-block text-[11px]', ['aria-hidden' => 'true'])
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider">
                                            {{ $platform === 'website' ? 'Website' : $value }}
                                        </span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex items-end justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-[220px]">
                    <span class="text-[8px] font-black uppercase tracking-[0.3em] text-gray-600 whitespace-nowrap">
                        {{ __('user.news.written_by', ['name' => $profileUser->name]) }}
                    </span>
                    <div class="h-px flex-grow bg-white/5"></div>
                </div>

                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="lang" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('user.news.lang_label') }}
                        </label>
                        <select id="lang" name="lang"
                                class="bg-[#050505] border border-border-subtle rounded-sm px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                            <option value="">{{ __('user.news.lang_all') }}</option>
                            @foreach ($locales as $code => $label)
                                <option value="{{ $code }}" @selected($langFilter === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="from" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('user.news.from_label') }}
                        </label>
                        <input id="from" type="date" name="from" value="{{ $fromFilter }}"
                               class="bg-[#050505] border border-border-subtle rounded-sm px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>

                    <div>
                        <label for="until" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('user.news.until_label') }}
                        </label>
                        <input id="until" type="date" name="until" value="{{ $untilFilter }}"
                               class="bg-[#050505] border border-border-subtle rounded-sm px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>

                    <button type="submit"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('user.news.filter_submit') }}
                    </button>

                    @if ($langFilter || $fromFilter || $untilFilter)
                        <a href="{{ route('users.news', $profileUser->username) }}"
                           class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('user.news.filter_reset') }}
                        </a>
                    @endif
                </form>
            </div>

            @if ($articles->isEmpty())
                <div class="text-center py-20">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-gray-600">{{ __('news.no_articles') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach ($articles as $article)
                        <x-news.article :news="$article" />
                    @endforeach
                </div>

                @if ($articles->hasPages())
                    <div class="flex justify-center">
                        {{ $articles->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
