{{--
    GC-Stats — Admin: edit news article

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $article->title)

@section('content')
    <a href="{{ route('admin.news.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.news.title') }}
    </a>

    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $article->title }}</h1>
            <p class="text-xs text-gray-500 mt-1">
                <span class="font-bold uppercase tracking-widest text-[10px] {{ $article->status === 'published' ? 'text-green-400' : 'text-gray-500' }}">
                    {{ __('admin.news.status.'.$article->status) }}
                </span>
                &middot; {{ __('admin.news.form.author_label') }}: {{ $article->author?->name }}
            </p>
        </div>

        <div class="flex gap-2">
            @if ($canPublish && $article->status !== 'published')
                <form method="POST" action="{{ route('admin.news.publish', $article) }}">
                    @csrf
                    <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('admin.news.publish') }}
                    </button>
                </form>
            @endif
            @if ($canArchive && $article->status !== 'archived')
                <form method="POST" action="{{ route('admin.news.archive', $article) }}">
                    @csrf
                    <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                        {{ __('admin.news.archive') }}
                    </button>
                </form>
            @endif
            @can('news.edit')
                <form method="POST" action="{{ route('admin.news.feature', $article) }}">
                    @csrf
                    <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle {{ $article->is_featured ? 'text-gc-yellow' : 'text-white' }} hover:bg-white/10">
                        {{ __('admin.news.feature') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.news.show-on-home', $article) }}">
                    @csrf
                    <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle {{ $article->show_on_home ? 'text-gc-yellow' : 'text-white' }} hover:bg-white/10">
                        {{ __('admin.news.show_on_home') }}
                    </button>
                </form>
            @endcan
            @if ($canArchive)
                <form method="POST" action="{{ route('admin.news.destroy', $article) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="__('admin.news.delete')"
                        :body="__('admin.news.delete_confirm')"
                        :trigger-label="__('admin.news.delete')"
                        :submit-label="__('admin.news.delete')"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3 mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('admin.news.update', $article) }}" class="lg:col-span-2 bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">
            @csrf
            @method('PUT')
            @include('admin.news._form')

            <button type="submit"
                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                {{ __('admin.news.form.save') }}
            </button>
        </form>

        <div class="space-y-6">
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.media.title') }}</h2>

                @if ($article->image_cover)
                    <img src="{{ $article->image_cover }}" alt="" class="w-full aspect-video object-cover rounded-sm border border-border-subtle">
                @endif

                <div class="grid grid-cols-3 gap-2">
                    @forelse ($images as $image)
                        <div class="relative group">
                            <img src="{{ $image->url }}" alt="" class="w-full aspect-square object-cover rounded-sm border border-border-subtle">
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition bg-black/60 p-1">
                                @if ($image->url !== $article->image_cover)
                                    <form method="POST" action="{{ route('admin.news.media.cover.update', [$article, $image]) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-[9px] font-bold uppercase tracking-widest text-white">{{ __('admin.news.media.set_as_cover') }}</button>
                                    </form>
                                @endif
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        navigator.clipboard.writeText(@js($image->url));
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    x-text="copied ? '{{ __('admin.news.media.copied') }}' : '{{ __('admin.news.media.copy') }}'"
                                    class="text-[9px] font-bold uppercase tracking-widest text-white"
                                >{{ __('admin.news.media.copy') }}</button>
                            </div>
                        </div>
                    @empty
                        <p class="col-span-3 text-xs text-gray-500">{{ __('admin.news.media.empty_for_article') }}</p>
                    @endforelse
                </div>

                <a href="{{ route('admin.news.media.index') }}"
                   class="block text-center w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                    {{ __('admin.news.media.title') }} &rarr;
                </a>
            </div>
        </div>
    </div>
@endsection
