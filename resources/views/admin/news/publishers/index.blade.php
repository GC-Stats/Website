{{--
    GC-Stats — Admin: publishers list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.news.publishers.title'))

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <form method="GET" action="{{ route('admin.news.publishers.index') }}" class="flex flex-wrap gap-2">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.news.publishers.search_placeholder') }}"
                   class="flex-1 max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.news.search_submit') }}
            </button>
        </form>

        @can('news.publishers.edit')
            <x-modal :title="__('admin.news.publishers.create')">
                <x-slot:trigger>
                    <button type="button"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] shrink-0">
                        {{ __('admin.news.publishers.create') }}
                    </button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('admin.news.publishers.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.publishers.form.name_label') }}</label>
                        <input type="text" name="name" required
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.publishers.form.slug_label') }}</label>
                        <input type="text" name="slug"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>
                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.news.publishers.form.save') }}
                    </button>
                </form>
            </x-modal>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <x-admin.sortable-th col="name" :sort="$sort" :direction="$direction">{{ __('admin.news.publishers.title') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="count" :sort="$sort" :direction="$direction"></x-admin.sortable-th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($publishers as $publisher)
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $publisher->name }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ trans_choice('admin.news.publishers.articles_count', $publisher->news_count, ['count' => $publisher->news_count]) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.news.publishers.show', $publisher) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.news.publishers.manage') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.news.publishers.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $publishers->links() }}
@endsection
