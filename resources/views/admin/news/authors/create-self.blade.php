{{--
    GC-Stats — Admin: create your author profile (self-service)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.news.authors.create'))

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-2">{{ __('admin.news.authors.create') }}</h1>
        <p class="text-sm text-gray-400 mb-6">{{ __('admin.news.authors.create_self_hint') }}</p>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3 mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.news.authors.store') }}" class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.authors.form.name_label') }}</label>
                <input type="text" name="name" required
                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.authors.form.slug_label') }}</label>
                <input type="text" name="slug"
                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.authors.form.bio_label') }}</label>
                <textarea name="bio" rows="4"
                          class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
            </div>
            <button type="submit"
                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                {{ __('admin.news.authors.form.save') }}
            </button>
        </form>
    </div>
@endsection
