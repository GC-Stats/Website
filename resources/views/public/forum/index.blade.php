{{--
    GC-Stats — Forum overview page

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('forum.title.index'))
@section('description', __('forum.title.index'))

@section('content')
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-black text-white">{{ __('forum.title.index') }}</h1>
            <a href="{{ route('forum.rules') }}" class="text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-white transition">
                {{ __('forum.rules.link') }}
            </a>
        </div>

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-black uppercase tracking-widest text-gray-400">{{ __('forum.category.general') }}</h2>
            <a href="{{ route('forum.general.index') }}" class="text-xs font-bold uppercase tracking-widest text-[var(--brand-yellow)] hover:underline">
                {{ __('forum.general.view_all') }}
            </a>
        </div>

        <div class="space-y-2">
            @forelse ($generalThreads as $thread)
                <a href="{{ route('forum.threads.show', $thread) }}"
                   class="flex items-center justify-between gap-3 bg-white/[0.03] border border-white/[0.06] hover:border-white/20 rounded-lg px-4 py-3 transition">
                    <span class="text-sm font-bold text-white truncate">{{ $thread->title }}</span>
                    <span class="text-[10px] text-gray-500 shrink-0">{{ $thread->messages_count }} {{ __('forum.thread.messages_count') }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-500 italic">{{ __('forum.thread.empty') }}</p>
            @endforelse
        </div>
    </div>
@endsection
