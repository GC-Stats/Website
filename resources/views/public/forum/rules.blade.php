{{--
    GC-Stats — Forum rules page

    Static page listing the forum's community rules — linked from the forum
    index/footer, and from the rules-popup component shown to signed-in
    users before their first post (see resources/views/components/forum/rules-popup.blade.php).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('forum.rules.title'))
@section('description', __('forum.rules.intro'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('forum.rules.title') }}
                </h1>
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2">
                    {{ __('forum.rules.last_updated', ['date' => date('d/m/Y')]) }}
                </p>
            </div>

            <p class="text-sm text-gray-400 leading-relaxed italic text-center px-4">
                {{ __('forum.rules.intro') }}
            </p>

            <div class="space-y-6 mt-8">
                @foreach (config('forum.rules_sections') as $index => $section)
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                        <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                            <span class="text-gc-yellow">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}.</span>
                            {{ __('forum.rules.sections.'.$section.'.title') }}
                        </h2>
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {{ __('forum.rules.sections.'.$section.'.text') }}
                        </p>
                    </div>
                @endforeach
            </div>

        </section>
    </div>
@endsection
