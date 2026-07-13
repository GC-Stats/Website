{{--
    GC-Stats — Transparency page

    Public page detailing how GC-Stats is developed, hosted and funded,
    with a short finance summary linking to the full ledger page.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('transparency.title'))

@php
    $formatAmount = function (float $value, string $currency = 'EUR') {
        $locale = app()->getLocale();
        $symbol = $currency === 'USD' ? '$' : '€';

        return $locale === 'fr'
            ? number_format($value, 2, ',', ' ') . ' ' . $symbol
            : $symbol . number_format($value, 2, '.', ',');
    };
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-12">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('transparency.title') }}
                </h1>
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.2em]">
                    {{ __('transparency.subtitle') }}
                </p>
            </div>

            <p class="text-sm text-gray-300 leading-relaxed text-center max-w-2xl mx-auto">
                {{ __('transparency.intro') }}
            </p>

            <div class="space-y-3">
                <div class="border-b border-border-subtle pb-2">
                    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                        <x-fas-code class="w-3.5 h-3.5 text-gc-yellow" aria-hidden="true" />
                        {{ __('transparency.dev.title') }}
                    </h2>
                </div>
                <p class="text-sm text-gray-300 leading-relaxed">
                    {!! __('transparency.dev.body') !!}
                </p>
                <a href="https://github.com/GC-Stats/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 bg-white hover:bg-gray-200 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                    <x-fab-github class="w-3.5 h-3.5 inline-block" aria-hidden="true" />
                    {{ __('transparency.dev.link') }}
                </a>
            </div>

            <div class="space-y-3">
                <div class="border-b border-border-subtle pb-2">
                    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                        <x-fas-server class="w-3.5 h-3.5 text-gc-yellow" aria-hidden="true" />
                        {{ __('transparency.hosting.title') }}
                    </h2>
                </div>
                <p class="text-sm text-gray-300 leading-relaxed">
                    {{ __('transparency.hosting.body') }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    @foreach([
                        ['key' => 'cdn', 'icon' => 'fas-bolt'],
                        ['key' => 'servers', 'icon' => 'fas-server'],
                        ['key' => 'mail', 'icon' => 'fas-envelope'],
                        ['key' => 'domain', 'icon' => 'fas-globe'],
                    ] as $provider)
                        <div class="flex gap-4 bg-bg-card border border-border-subtle rounded-sm p-5 hover:border-gc-yellow/40 transition-colors">
                            <div class="shrink-0 w-10 h-10 rounded-sm bg-bg-main border border-border-subtle flex items-center justify-center">
                                <x-dynamic-component :component="$provider['icon']" class="w-4 h-4 text-gc-yellow" aria-hidden="true" />
                            </div>
                            <div>
                                <p class="text-sm font-black text-white">
                                    {{ __('transparency.hosting.providers.' . $provider['key'] . '.name') }}
                                </p>
                                <p class="text-[9px] font-black uppercase tracking-widest text-gc-yellow mt-0.5">
                                    {{ __('transparency.hosting.providers.' . $provider['key'] . '.role') }}
                                </p>
                                <p class="text-xs text-gray-400 leading-relaxed mt-2">
                                    {{ __('transparency.hosting.providers.' . $provider['key'] . '.body') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-3">
                <div class="border-b border-border-subtle pb-2">
                    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                        <x-fas-database class="w-3.5 h-3.5 text-gc-yellow" aria-hidden="true" />
                        {{ __('transparency.data.title') }}
                    </h2>
                </div>
                <p class="text-sm text-gray-300 leading-relaxed">
                    {{ __('transparency.data.body') }}
                </p>
                <a href="{{ route('data') }}"
                   class="inline-flex items-center gap-2 bg-white hover:bg-gray-200 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                    <x-fas-database class="w-3.5 h-3.5 inline-block" aria-hidden="true" />
                    {{ __('transparency.data.link') }}
                </a>
            </div>

            <div class="space-y-3" x-data="{ currency: 'EUR' }">
                <div class="border-b border-border-subtle pb-2 flex items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                        <x-fas-coins class="w-3.5 h-3.5 text-gc-yellow" aria-hidden="true" />
                        {{ __('transparency.finance.title') }}
                    </h2>
                    <div class="flex items-center gap-1 bg-bg-card border border-border-subtle rounded-sm p-0.5 no-accent-ring">
                        <button type="button" @click="currency = 'EUR'"
                                class="px-2.5 py-1 text-[10px] font-black uppercase rounded-sm transition"
                                :class="currency === 'EUR' ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 hover:text-white'">
                            EUR
                        </button>
                        <button type="button" @click="currency = 'USD'"
                                class="px-2.5 py-1 text-[10px] font-black uppercase rounded-sm transition"
                                :class="currency === 'USD' ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 hover:text-white'">
                            USD
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-300 leading-relaxed">
                    {{ __('transparency.finance.body') }}
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-5 text-center">
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-500">{{ __('transparency.finance.income') }}</p>
                        <p class="text-xl font-black text-green-400 mt-2"
                           x-text="currency === 'EUR' ? @js($formatAmount($totals['EUR']['income'], 'EUR')) : @js($formatAmount($totals['USD']['income'], 'USD'))"></p>
                    </div>
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-5 text-center">
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-500">{{ __('transparency.finance.expense') }}</p>
                        <p class="text-xl font-black text-red-400 mt-2"
                           x-text="currency === 'EUR' ? @js($formatAmount($totals['EUR']['expense'], 'EUR')) : @js($formatAmount($totals['USD']['expense'], 'USD'))"></p>
                    </div>
                    <div class="bg-bg-main border border-gc-yellow/30 rounded-sm p-5 text-center">
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-500">{{ __('transparency.finance.balance') }}</p>
                        <p class="text-xl font-black text-white mt-2"
                           x-text="currency === 'EUR' ? @js($formatAmount($totals['EUR']['balance'], 'EUR')) : @js($formatAmount($totals['USD']['balance'], 'USD'))"></p>
                    </div>
                </div>

                @if($lastEntry)
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest text-center mt-2">
                        {{ __('transparency.finance.last_update', ['date' => $lastEntry->entry_date->translatedFormat('d M Y')]) }}
                    </p>
                @else
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest text-center mt-2">
                        {{ __('transparency.finance.no_entry') }}
                    </p>
                @endif

                <div class="text-center">
                    <a href="{{ route('finance') }}"
                       class="inline-flex items-center gap-2 bg-[var(--brand-yellow)] hover:brightness-110 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                        <x-fas-book class="w-3.5 h-3.5 inline-block" aria-hidden="true" />
                        {{ __('transparency.finance.link') }}
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
