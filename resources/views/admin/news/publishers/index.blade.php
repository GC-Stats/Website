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
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.news.publishers.search_placeholder') }}"
                   class="flex-1 max-w-sm bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('admin.news.search_submit') }}
            </button>
        </form>

        @can('news.publishers.edit')
            <x-modal :title="__('admin.news.publishers.create')">
                <x-slot:trigger>
                    <button type="button"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 shrink-0">
                        {{ __('admin.news.publishers.create') }}
                    </button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('admin.news.publishers.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.publishers.form.name_label') }}</label>
                        <input type="text" name="name" required
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.publishers.form.slug_label') }}</label>
                        <input type="text" name="slug"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>
                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('admin.news.publishers.form.save') }}
                    </button>
                </form>
            </x-modal>
        @endcan
    </div>

    <div class="bg-bg-card border border-border-subtle rounded-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-border-subtle text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.news.publishers.title') }}</th>
                    <th class="px-4 py-3"></th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($publishers as $publisher)
                    <tr class="border-b border-border-subtle last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $publisher->name }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ trans_choice('admin.news.publishers.articles_count', $publisher->news_count, ['count' => $publisher->news_count]) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.news.publishers.show', $publisher) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
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
