{{--
    GC-Stats — Finance page

    Public ledger ("livre de comptes") listing every income and expense
    of the GC-Stats project. Entries are stored in the database and
    managed via the internal API. Shows the last 12 months by default,
    with older months available behind a toggle.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('finance.title'))

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
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-10" x-data="{ currency: 'EUR' }">

            <div class="border-b border-border-subtle pb-6 text-center relative">
                <div class="absolute right-0 top-0 flex items-center gap-1 bg-bg-card border border-border-subtle rounded-sm p-0.5 no-accent-ring">
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
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('finance.title') }}
                </h1>
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2 tracking-[0.2em]">
                    {{ __('finance.subtitle') }}
                </p>
            </div>

            <p class="text-sm text-gray-300 leading-relaxed text-center max-w-2xl mx-auto">
                {!! __('finance.intro', [
                    'link' => '<a href="' . route('transparency') . '" class="text-gc-yellow hover:underline">' . __('finance.transparency_link') . '</a>',
                ]) !!}
            </p>

            @php
                $summaryColumns = [
                    ['title' => __('finance.current_year.title', ['year' => now()->year]), 'data' => $currentYear],
                    ['title' => __('finance.summary.title'), 'data' => $totals],
                    ['title' => __('finance.average.title'), 'data' => $average],
                ];
            @endphp
            <div class="grid grid-cols-3 gap-2">
                @foreach($summaryColumns as $column)
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-3">
                        <p class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-400 text-center pb-2 mb-2 border-b border-border-subtle">
                            {{ $column['title'] }}
                        </p>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[8px] font-bold uppercase text-gray-500">{{ __('finance.summary.income') }}</span>
                                <span class="text-xs font-black text-green-400 whitespace-nowrap"
                                      x-text="currency === 'EUR' ? @js($formatAmount($column['data']['EUR']['income'], 'EUR')) : @js($formatAmount($column['data']['USD']['income'], 'USD'))"></span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[8px] font-bold uppercase text-gray-500">{{ __('finance.summary.expense') }}</span>
                                <span class="text-xs font-black text-red-400 whitespace-nowrap"
                                      x-text="currency === 'EUR' ? @js($formatAmount($column['data']['EUR']['expense'], 'EUR')) : @js($formatAmount($column['data']['USD']['expense'], 'USD'))"></span>
                            </div>
                            <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-border-subtle">
                                <span class="text-[8px] font-bold uppercase text-gray-500">{{ __('finance.summary.balance') }}</span>
                                <span class="text-xs font-black text-white whitespace-nowrap"
                                      x-text="currency === 'EUR' ? @js($formatAmount($column['data']['EUR']['balance'], 'EUR')) : @js($formatAmount($column['data']['USD']['balance'], 'USD'))"></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($recentEntries->isEmpty() && $olderEntries->isEmpty())
                <p class="text-sm text-gray-500 text-center py-12">
                    {{ __('finance.empty') }}
                </p>
            @else
                <div class="space-y-10" x-data="{ showOlder: false }">
                    <div class="space-y-10">
                        <p class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-500">{{ __('finance.recent_period') }}</p>

                        @forelse($recentEntries as $month => $monthEntries)
                            @include('partials.finance-month', ['month' => $month, 'monthEntries' => $monthEntries])
                        @empty
                            <p class="text-sm text-gray-500 text-center py-6">{{ __('finance.empty') }}</p>
                        @endforelse
                    </div>

                    @if($olderEntries->isNotEmpty())
                        <div class="text-center">
                            <button type="button" @click="showOlder = !showOlder"
                                    class="inline-flex items-center gap-2 border border-border-subtle hover:border-gc-yellow text-[10px] font-black uppercase tracking-widest text-gray-300 hover:text-white px-6 py-2 rounded-sm transition">
                                <x-fas-chevron-down class="w-3 h-3 transition-transform" :class="showOlder ? 'rotate-180' : ''" aria-hidden="true" />
                                <span x-text="showOlder ? @js(__('finance.hide_older')) : @js(__('finance.show_older'))"></span>
                            </button>
                        </div>

                        <div class="space-y-10" x-show="showOlder" x-cloak>
                            @foreach($olderEntries as $month => $monthEntries)
                                @include('partials.finance-month', ['month' => $month, 'monthEntries' => $monthEntries])
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </div>
@endsection
