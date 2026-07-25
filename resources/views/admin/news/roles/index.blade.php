{{--
    GC-Stats — Admin: publisher roles list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.news.roles.title'))

@section('content')
    <a href="{{ route('admin.news.publishers.show', $publisher) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-4">
        &larr; {{ __('admin.news.roles.back_to_publisher', ['publisher' => $publisher->name]) }}
    </a>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ __('admin.news.roles.title') }}</h1>

        <x-modal :title="__('admin.news.roles.new_role.title')">
            <x-slot:trigger>
                <button type="button"
                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                    {{ __('admin.news.roles.new_role.title') }}
                </button>
            </x-slot:trigger>

            <form method="POST" action="{{ route('admin.news.publishers.roles.store', $publisher) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                        {{ __('admin.news.roles.new_role.name_label') }}
                    </label>
                    <input type="text" name="name" required pattern="[A-Za-z0-9_\-]+"
                           class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    @error('name')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                    {{ __('admin.news.roles.new_role.submit') }}
                </button>
            </form>
        </x-modal>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach ($roles as $role)
            <a href="{{ route('admin.news.publishers.roles.show', [$publisher, $role]) }}"
               class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl hover:border-gc-yellow/50 transition-all group">
                <h2 class="text-sm font-black uppercase tracking-widest text-white group-hover:text-gc-yellow transition-colors mb-2">{{ $role->name }}</h2>
                <p class="text-xs text-gray-500">{{ trans_choice('admin.news.roles.member_count', $role->users_count, ['count' => $role->users_count]) }}</p>
            </a>
        @endforeach
    </div>
@endsection
