{{--
    GC-Stats — "Edit a page" help page

    Static help page explaining how users can request edits/corrections
    to player, team or tournament pages on GC-Stats.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('help/edit_page.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('help/edit_page.title') }}
                </h1>
            </div>

            <p class="text-sm text-gray-400 leading-relaxed italic text-center px-4">
                {{ __('help/edit_page.intro') }}
            </p>

            <div class="space-y-6 mt-8">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative overflow-hidden">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('help/edit_page.discord.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300">
                            {!! __('help/edit_page.discord.body') !!}
                        </p>
                        <a href="https://discord.gg/JZgVmAFK9a" target="_blank" class="inline-flex items-center gap-2 bg-[#5865F2] hover:bg-[#4752C4] text-white text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                            <x-fab-discord class="w-3.5 h-3.5 inline-block" aria-hidden="true" />
                            {{ __('help/edit_page.discord.btn') }}
                        </a>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('help/edit_page.request.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        {!! __('help/edit_page.request.body') !!}
                    </p>

                    <div class="mt-4 bg-black/30 border-l-2 border-gc-yellow p-3">
                        <p class="text-[10px] font-mono text-gc-yellow">{{ __("help/edit_page.request.warning") }}</p>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl border-l-4 border-l-gc-yellow">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-2 italic">
                        {{ __('help/edit_page.logo.title') }}
                    </h2>
                    <p class="text-xs text-gray-400 mb-4">
                        {{ __('help/edit_page.logo.body') }}
                    </p>
                    <div class="bg-bg-main p-3 rounded-sm border border-border-subtle">
                        <code class="text-[10px] text-gray-300 font-mono">/upload-image [link] [file]</code>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
