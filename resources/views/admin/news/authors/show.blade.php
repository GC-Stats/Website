{{--
    GC-Stats — Admin: author profile (100% editable)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $author->name)

@section('content')
    @can('news.authors.view')
        <a href="{{ route('admin.news.authors.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
            &larr; {{ __('admin.news.authors.title') }}
        </a>
    @endcan

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $author->name }}</h1>
        @can('news.authors.delete')
            <form method="POST" action="{{ route('admin.news.authors.destroy', $author) }}">
                @csrf
                @method('DELETE')
                <x-confirm-modal
                    :title="__('admin.news.authors.delete')"
                    :body="__('admin.news.authors.delete').' — '.$author->name"
                    :trigger-label="__('admin.news.authors.delete')"
                    :submit-label="__('admin.news.authors.delete')"
                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                />
            </form>
        @endcan
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.authors.form.name_label') }}</h2>
                <x-logo-upload-form
                    :current-url="$author->logo"
                    :action-url="route('admin.news.authors.logo.update', $author)"
                    :submit-label="__('admin.news.authors.form.save')"
                />
                @error('logo')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
                <form method="POST" action="{{ route('admin.news.authors.update', $author) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.authors.form.name_label') }}</label>
                        <input type="text" name="name" value="{{ $author->name }}" required
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.authors.form.slug_label') }}</label>
                        <input type="text" name="slug" value="{{ $author->slug }}"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.authors.form.bio_label') }}</label>
                        <textarea name="bio" rows="4"
                                  class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ $author->bio }}</textarea>
                    </div>
                    @foreach (['twitter', 'discord', 'instagram', 'twitch', 'youtube', 'website'] as $social)
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ Str::headline($social) }}</label>
                            <input type="text" name="socials[{{ $social }}]" value="{{ $author->socials[$social] ?? '' }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                    @endforeach

                    @can('news.authors.edit')
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.authors.form.user_label') }}</label>
                            <input type="number" name="user_id" value="{{ $author->user_id }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        </div>
                    @endcan

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.news.authors.form.save') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
