{{--
    GC-Stats — Admin: news articles list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.news.title'))

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <form method="GET" action="{{ route('admin.news.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.news.search_placeholder') }}"
                   class="flex-1 max-w-sm bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

            <select name="status" onchange="this.form.submit()"
                    class="bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                <option value="">{{ __('admin.news.search_submit') }}</option>
                <option value="draft" @selected($status === 'draft')>{{ __('admin.news.status.draft') }}</option>
                <option value="published" @selected($status === 'published')>{{ __('admin.news.status.published') }}</option>
                <option value="archived" @selected($status === 'archived')>{{ __('admin.news.status.archived') }}</option>
            </select>

            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('admin.news.search_submit') }}
            </button>
        </form>

        @can('news.action.create')
            <a href="{{ route('admin.news.create') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 shrink-0">
                {{ __('admin.news.create') }}
            </a>
        @endcan
    </div>

    <div class="bg-bg-card border border-border-subtle rounded-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-border-subtle text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.news.form.title_label') }}</th>
                    <th class="px-4 py-3">{{ __('admin.news.form.author_label') }}</th>
                    <th class="px-4 py-3">{{ __('admin.news.form.publisher_label') }}</th>
                    <th class="px-4 py-3">{{ __('admin.news.form.status_label') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($news as $article)
                    <tr class="border-b border-border-subtle last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $article->title }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $article->author?->name }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $article->publisher?->name }}</td>
                        <td class="px-4 py-3 text-xs">
                            <span class="font-bold uppercase tracking-widest text-[10px] {{ $article->status === 'published' ? 'text-green-400' : 'text-gray-500' }}">
                                {{ __('admin.news.status.'.$article->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (auth()->user()->can('news.edit') || $editablePublisherIds->contains($article->publisher_id))
                                <a href="{{ route('admin.news.edit', $article) }}"
                                   class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                    {{ __('admin.news.manage') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.news.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $news->links() }}
@endsection
