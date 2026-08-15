{{--
    GC-Stats — New "general" forum thread form

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('forum.general.new_thread'))
@section('description', __('forum.general.new_thread'))

@section('content')
    <div class="max-w-2xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-black text-white mb-6">{{ __('forum.general.new_thread') }}</h1>

        <form method="POST" action="{{ route('forum.general.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('forum.general.title_label') }}</label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="150" required
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                @error('title')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('forum.message.placeholder') }}</label>
                <textarea name="body" rows="6" maxlength="5000" required
                          class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="font-bold uppercase text-xs tracking-widest px-6 py-3 rounded-lg transition active:scale-95 bg-gc-yellow/10 border border-gc-yellow/40 text-gc-yellow hover:bg-gc-yellow/20">
                {{ __('forum.general.submit') }}
            </button>
        </form>
    </div>
@endsection
