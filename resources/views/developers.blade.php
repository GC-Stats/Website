{{--
    GC-Stats — Developers page

    Static page providing information for developers about GC-Stats'
    public API and integrations.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('developers.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('developers.title') }}
                </h1>
            </div>

            <p class="text-sm text-gray-400 leading-relaxed italic text-center px-4">
                {{ __('developers.intro') }}
            </p>

            <div class="space-y-6 mt-8">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative overflow-hidden">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('developers.api_key.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {!! __('developers.api_key.body') !!}
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-[#050505] p-4 border-t-2 border-gc-yellow">
                                <p class="text-[10px] uppercase font-bold text-gray-500 mb-2">{{ __('developers.api_key.get_a_key') }}</p>
                                <p class="text-[11px] text-gc-yellow mb-1 font-bold">
                                    {{ __('developers.api_key.warning') }}
                                </p>
                                <ul class="text-[11px] font-mono text-gray-400 list-disc list-inside space-y-1">
                                    <li>{{ __('developers.api_key.step_1') }}</li>
                                    <li>{{ __('developers.api_key.step_2') }}</li>
                                    <li>{{ __('developers.api_key.step_3') }}</li>
                                </ul>
                            </div>
                            <div class="bg-[#050505] p-4 border-t-2 border-red-600">
                                <p class="text-[10px] uppercase font-bold text-red-500 mb-2">{{ __('developers.api_key.forbidden_title') }}</p>
                                <p class="text-xs text-gray-300 leading-relaxed">{{ __('developers.api_key.forbidden_text') }}</p>
                            </div>
                        </div>

                        <div class="flex justify-center pt-2">
                            <a href="https://discord.gg/JZgVmAFK9a" target="_blank" class="inline-flex items-center gap-2 bg-[#5865F2] hover:bg-[#4752C4] text-white text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                                <x-fab-discord class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> {{ __('developers.api_key.btn') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('developers.swagger.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed mb-4">
                        {!! __('developers.swagger.body') !!}
                    </p>

                    <a href="https://api.gc-stats.app/doc/" target="_blank" class="inline-flex items-center gap-2 bg-white hover:bg-gray-200 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                        <x-fas-code class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> {{ __('developers.swagger.btn') }}
                    </a>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('developers.dashboard.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed mb-4">
                        {{ __('developers.dashboard.body') }}
                    </p>

                    <a href="https://api.gc-stats.app/dashboard" target="_blank" class="inline-flex items-center gap-2 bg-white hover:bg-gray-200 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                        <x-fas-gauge class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> {{ __('developers.dashboard.btn') }}
                    </a>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl relative">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="text-gc-yellow">#</span> {{ __('developers.opendata.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed mb-4">
                        {{ __('developers.opendata.body') }}
                    </p>

                    <a href="https://data.gc-stats.app" target="_blank" class="inline-flex items-center gap-2 bg-white hover:bg-gray-200 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                        <x-fas-database class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> {{ __('developers.opendata.btn') }}
                    </a>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl border-l-4 border-l-gc-yellow">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-2 italic">
                        {{ __('developers.git.title') }}
                    </h2>
                    <p class="text-xs text-gray-400 mb-4 leading-relaxed">
                        {{ __('developers.git.body') }}
                    </p>
                    <a href="https://github.com/GC-Stats/Website/" target="_blank" class="bg-bg-main p-3 rounded-sm border border-border-subtle flex items-center justify-between">
                        <span class="text-[10px] text-gray-300 font-mono flex items-center gap-2">
                            <x-fab-github class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> github.com/GC-Stats/Website
                        </span>
                        <span class="text-[9px] font-mono text-gc-yellow uppercase tracking-wider font-bold">
                            {{ __('developers.git.badge') }}
                        </span>
                    </a>
                    <a href="https://github.com/GC-Stats/API/" target="_blank" class="bg-bg-main p-3 rounded-sm border border-border-subtle flex items-center justify-between mt-2">
                        <span class="text-[10px] text-gray-300 font-mono flex items-center gap-2">
                            <x-fab-github class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> github.com/GC-Stats/API
                        </span>
                        <span class="text-[9px] font-mono text-gc-yellow uppercase tracking-wider font-bold">
                            {{ __('developers.git.badge') }}
                        </span>
                    </a>
                    <a href="https://github.com/GC-Stats/DiscordBot/" target="_blank" class="bg-bg-main p-3 rounded-sm border border-border-subtle flex items-center justify-between mt-2">
                        <span class="text-[10px] text-gray-300 font-mono flex items-center gap-2">
                            <x-fab-github class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> github.com/GC-Stats/DiscordBot
                        </span>
                        <span class="text-[9px] font-mono text-gc-yellow uppercase tracking-wider font-bold">
                            {{ __('developers.git.badge') }}
                        </span>
                    </a>
                    <a href="https://github.com/GC-Stats/OpenData/" target="_blank" class="bg-bg-main p-3 rounded-sm border border-border-subtle flex items-center justify-between mt-2">
                        <span class="text-[10px] text-gray-300 font-mono flex items-center gap-2">
                            <x-fab-github class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> github.com/GC-Stats/OpenData
                        </span>
                        <span class="text-[9px] font-mono text-gc-yellow uppercase tracking-wider font-bold">
                            {{ __('developers.git.badge') }}
                        </span>
                    </a>
                    <a href="https://github.com/GC-Stats/RiotRelay/" target="_blank" class="bg-bg-main p-3 rounded-sm border border-border-subtle flex items-center justify-between mt-2">
                        <span class="text-[10px] text-gray-300 font-mono flex items-center gap-2">
                            <x-fab-github class="w-3.5 h-3.5 inline-block" aria-hidden="true" /> github.com/GC-Stats/RiotRelay
                        </span>
                        <span class="text-[9px] font-mono text-gc-yellow uppercase tracking-wider font-bold">
                            {{ __('developers.git.badge') }}
                        </span>
                    </a>
                </div>

            </div>
        </section>
    </div>
@endsection
