{{--
    GC-Stats — Data Explorer documentation

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('data_explorer.docs.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <article class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-8">
            <div class="border-b border-border-subtle pb-6">
                <a href="{{ route('data-explorer.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-500 hover:text-white transition mb-4">
                    @svg('fas-arrow-left', 'w-3 h-3', ['aria-hidden' => 'true'])
                    {{ __('data_explorer.docs.back_to_query') }}
                </a>
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('data_explorer.docs.title') }}</h1>
                <p class="text-sm text-gray-400 mt-3 leading-relaxed">{{ __('data_explorer.docs.intro') }}</p>
            </div>

            {{-- Ground rules --}}
            <section class="space-y-4">
                <h2 class="text-lg font-black uppercase tracking-tight text-white">{{ __('data_explorer.docs.rules_title') }}</h2>
                <p class="text-xs text-gray-500">{{ __('data_explorer.docs.rules_intro') }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach (['cost', 'limit', 'usage', 'alerts'] as $rule)
                        <div class="bg-bg-card border border-border-subtle rounded-sm p-5 space-y-2">
                            <p class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('data_explorer.docs.rule_'.$rule.'_title') }}</p>
                            <p class="text-xs text-gray-400 leading-relaxed">{{ __('data_explorer.docs.rule_'.$rule.'_body') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Key creation --}}
            <section class="space-y-4">
                <h2 class="text-lg font-black uppercase tracking-tight text-white">{{ __('data_explorer.docs.key_title') }}</h2>

                <div class="bg-red-500/10 border border-red-500/30 rounded-sm px-5 py-4 flex gap-3">
                    @svg('fas-triangle-exclamation', 'w-5 h-5 text-red-400 shrink-0 mt-0.5', ['aria-hidden' => 'true'])
                    <div>
                        <p class="text-sm font-bold text-red-400">{{ __('data_explorer.docs.key_confidentiality_title') }}</p>
                        <p class="text-xs text-gray-300 leading-relaxed mt-1">{{ __('data_explorer.docs.key_confidentiality_body') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-5 space-y-3">
                        <p class="text-sm font-bold text-white">{{ __('data_explorer.docs.openai_title') }}</p>
                        <ol class="space-y-2 text-xs text-gray-400 leading-relaxed list-decimal list-inside">
                            @foreach (__('data_explorer.docs.openai_steps') as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="bg-bg-card border border-border-subtle rounded-sm p-5 space-y-3">
                        <p class="text-sm font-bold text-white">{{ __('data_explorer.docs.anthropic_title') }}</p>
                        <ol class="space-y-2 text-xs text-gray-400 leading-relaxed list-decimal list-inside">
                            @foreach (__('data_explorer.docs.anthropic_steps') as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                <a href="{{ route('data-explorer.settings') }}"
                   class="inline-flex items-center gap-2 font-bold uppercase text-xs tracking-widest px-5 py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                    {{ __('data_explorer.settings.title') }}
                    @svg('fas-arrow-right', 'w-3 h-3', ['aria-hidden' => 'true'])
                </a>
            </section>

            {{-- Help --}}
            <section class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                    <span class="text-gc-yellow">#</span> {{ __('data_explorer.docs.help_title') }}
                </h2>
                <p class="text-sm text-gray-300 leading-relaxed">{{ __('data_explorer.docs.help_body') }}</p>
                <a href="https://discord.gg/JZgVmAFK9a" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 bg-[#5865F2] hover:bg-[#4752C4] text-white text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                    <x-fab-discord class="w-3.5 h-3.5 inline-block" aria-hidden="true" />
                    {{ __('data_explorer.docs.help_btn') }}
                </a>
            </section>
        </article>
    </div>
@endsection
