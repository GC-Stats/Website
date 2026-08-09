{{--
    GC-Stats — Personal dashboard: overview

    Equivalent of organization/dashboard/index.blade.php for a lone author
    with no organization — no staff/roles to summarize here, just the
    author's own profile snapshot and their article counts by status.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', __('personal_dashboard.title'))

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-4 min-w-0">
            @if ($author?->logo)
                <img src="{{ $author->logo }}" alt="" class="w-14 h-14 object-contain border border-white/10 rounded-lg bg-black/40 p-2 shrink-0">
            @else
                <div class="w-14 h-14 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10 shrink-0">
                    <span class="text-lg font-black text-[var(--brand-yellow)]">{{ strtoupper(substr($author->name ?? auth()->user()->name, 0, 1)) }}</span>
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="text-xl font-black uppercase tracking-tight text-white truncate">{{ $author->name ?? auth()->user()->name }}</h1>
                <p class="text-xs text-gray-500">{{ __('personal_dashboard.title') }}</p>
            </div>
        </div>

        <a href="{{ route('personal-dashboard.news.author.my') }}"
           class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            {{ __('personal_dashboard.profile.heading') }}
        </a>
    </div>

    @unless ($author)
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl mb-6">
            <p class="text-sm text-gray-400">{{ __('personal_dashboard.profile.missing') }}</p>
        </div>
    @endunless

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">{{ __('personal_dashboard.stats.draft') }}</p>
            <p class="text-2xl font-black text-white">{{ $draftCount }}</p>
        </div>
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">{{ __('personal_dashboard.stats.published') }}</p>
            <p class="text-2xl font-black text-white">{{ $publishedCount }}</p>
        </div>
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">{{ __('personal_dashboard.stats.archived') }}</p>
            <p class="text-2xl font-black text-white">{{ $archivedCount }}</p>
        </div>
    </div>

    <a href="{{ route('personal-dashboard.news.index') }}"
       class="inline-flex items-center gap-2 font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
        @svg('fas-newspaper', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
        {{ __('personal_dashboard.nav.news') }}
    </a>
@endsection
