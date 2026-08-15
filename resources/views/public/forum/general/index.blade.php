{{--
    GC-Stats — Forum "general" thread list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('forum.category.general'))
@section('description', __('forum.category.general'))

@section('content')
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-black text-white">{{ __('forum.category.general') }}</h1>
            @auth
                <a href="{{ route('forum.general.create') }}"
                   class="font-bold uppercase text-xs tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-gc-yellow/10 border border-gc-yellow/40 text-gc-yellow hover:bg-gc-yellow/20">
                    {{ __('forum.general.new_thread') }}
                </a>
            @endauth
        </div>

        <div class="space-y-2">
            @forelse ($threads as $thread)
                <a href="{{ route('forum.threads.show', $thread) }}"
                   class="flex items-center justify-between gap-3 bg-white/[0.03] border border-white/[0.06] hover:border-white/20 rounded-lg px-4 py-3 transition">
                    <span class="text-sm font-bold text-white truncate">{{ $thread->title }}</span>
                    <span class="text-[10px] text-gray-500 shrink-0">{{ $thread->messages_count }} {{ __('forum.thread.messages_count') }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-500 italic">{{ __('forum.thread.empty') }}</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $threads->links() }}
        </div>
    </div>
@endsection
