{{--
    GC-Stats — Data Explorer screen

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('data_explorer.index.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6"
                  x-data="dataExplorer({
                    executeUrl: '{{ route('data-explorer.execute') }}',
                    blocked: {{ $usage['source'] === null ? 'true' : 'false' }},
                  })">
            <div class="border-b border-border-subtle pb-6 flex items-center justify-between gap-4">
                <div class="flex-1"></div>
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white text-center">{{ __('data_explorer.index.title') }}</h1>
                <div class="flex-1 flex justify-end gap-3">
                    <a href="{{ route('data-explorer.builder') }}" title="{{ __('data_explorer.index.builder_link') }}"
                       class="text-gray-500 hover:text-white transition">
                        @svg('fas-table', 'w-4 h-4', ['aria-hidden' => 'true'])
                    </a>
                    <a href="{{ route('data-explorer.docs') }}" title="{{ __('data_explorer.index.docs_link') }}"
                       class="text-gray-500 hover:text-white transition">
                        @svg('fas-circle-question', 'w-4 h-4', ['aria-hidden' => 'true'])
                    </a>
                    <a href="{{ route('data-explorer.settings') }}" title="{{ __('data_explorer.index.settings_link') }}"
                       class="text-gray-500 hover:text-white transition">
                        @svg('fas-gear', 'w-4 h-4', ['aria-hidden' => 'true'])
                    </a>
                </div>
            </div>

            <x-data-explorer.status-banner :usage="$usage" />

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                {{-- Prompt-writing help — this feature only works well with a precise, specific question --}}
                <div class="bg-white/5 border border-border-subtle rounded-sm px-4 py-3 flex gap-3">
                    @svg('fas-lightbulb', 'w-4 h-4 text-gc-yellow shrink-0 mt-0.5', ['aria-hidden' => 'true'])
                    <div class="text-xs text-gray-400 leading-relaxed space-y-1">
                        <p class="text-gray-300 font-semibold">{{ __('data_explorer.index.help_title') }}</p>
                        <p>{{ __('data_explorer.index.help_body') }}</p>
                        <p class="italic text-gray-500">{{ __('data_explorer.index.help_example') }}</p>
                    </div>
                </div>

                <textarea x-model="prompt" :disabled="blocked || loading" rows="3"
                          placeholder="{{ __('data_explorer.index.prompt_placeholder') }}"
                          class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition disabled:opacity-50"></textarea>

                <button type="button" @click="submit()" :disabled="blocked || loading || !prompt.trim()"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 disabled:opacity-50">
                    <span x-show="!loading">{{ __('data_explorer.index.submit') }}</span>
                    <span x-show="loading" x-cloak>{{ __('data_explorer.index.loading') }}</span>
                </button>

                <div x-show="error" x-cloak class="bg-red-500/10 border border-red-500/30 rounded-sm px-4 py-3 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-red-400" x-text="error"></p>
                        <p class="text-[11px] text-red-400/60 mt-1" x-show="errorId" x-cloak>
                            {{ __('data_explorer.index.error_id') }}: <span class="font-mono" x-text="errorId"></span>
                        </p>
                    </div>
                    <button type="button" x-show="canRetry" @click="submit()"
                            class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                        {{ __('data_explorer.index.retry') }}
                    </button>
                </div>

                <x-data-explorer.result-panel>
                    <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-white/5 border border-border-subtle text-gray-400">
                        {{ __('data_explorer.index.provider_used') }}: <span x-text="response.provider_used || '—'"></span>
                    </span>
                </x-data-explorer.result-panel>
            </div>
        </section>
    </div>
@endsection
