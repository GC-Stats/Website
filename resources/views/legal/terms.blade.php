{{--
    GC-Stats — Terms of service page

    Static page containing the site's terms of service.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('terms.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('terms.title') }}
                </h1>
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2">
                    {{ __('terms.last_updated', ['date' => date('d/m/Y')]) }}
                </p>
            </div>

            <p class="text-sm text-gray-400 leading-relaxed italic text-center px-4">
                {{ __('terms.intro') }}
            </p>

            <div class="space-y-6 mt-8">

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">01.</span> {{ __('terms.service.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        {{ __('terms.service.text') }}
                    </p>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">02.</span> {{ __('terms.access.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        {{ __('terms.access.text') }}
                    </p>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">03.</span> {{ __('terms.riot_data.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {{ __('terms.riot_data.text') }}
                        </p>
                        <ul class="space-y-2 pl-4">
                            @foreach(__('terms.riot_data.items') as $item)
                                <li class="text-sm text-gray-400 leading-relaxed flex items-start gap-2">
                                    <span class="text-gc-yellow mt-1 flex-shrink-0">—</span>
                                    {{ $item }}
                                </li>
                            @endforeach
                        </ul>
                        <div class="bg-bg-main border-l-2 border-gc-yellow p-4">
                            <p class="text-xs text-gray-400 italic leading-snug">
                                {{ __('terms.riot_data.opt_in') }}
                            </p>
                        </div>
                        <div class="bg-bg-main border-l-2 border-gc-yellow p-4">
                            <p class="text-xs text-gray-400 italic leading-snug">
                                {{ __('terms.riot_data.correction') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">04.</span> {{ __('terms.prohibited.title') }}
                    </h2>
                    <div class="space-y-3">
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {{ __('terms.prohibited.text') }}
                        </p>
                        <ul class="space-y-2 pl-4">
                            @foreach(__('terms.prohibited.items') as $item)
                                <li class="text-sm text-gray-400 leading-relaxed flex items-start gap-2">
                                    <span class="text-gc-yellow mt-1 flex-shrink-0">—</span>
                                    {{ $item }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">05.</span> {{ __('terms.api.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        {{ __('terms.api.text') }}
                    </p>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">06.</span> {{ __('terms.ip.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        {{ __('terms.ip.text') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase mb-3 tracking-widest italic">
                            {{ __('terms.liability.title') }}
                        </h3>
                        <p class="text-xs text-gray-400 leading-normal">
                            {{ __('terms.liability.text') }}
                        </p>
                    </div>

                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase mb-3 tracking-widest italic">
                            {{ __('terms.changes.title') }}
                        </h3>
                        <p class="text-xs text-gray-400 leading-normal mb-4">
                            {{ __('terms.changes.text') }}
                        </p>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <h3 class="text-[10px] font-black text-gray-500 uppercase mb-3 tracking-widest italic">
                        {{ __('terms.contact.title') }}
                    </h3>
                    <p class="text-xs text-gray-400 leading-normal mb-3">
                        {{ __('terms.contact.text') }}
                    </p>
                    <p class="text-[10px] font-bold text-white uppercase p-2 bg-bg-main border border-white/5 inline-block">
                        {{ __('terms.contact.email') }}
                    </p>
                </div>

                <div class="bg-bg-main border border-border-subtle rounded-sm p-6">
                    <div class="flex items-start justify-center gap-6">
                        <div class="text-gc-yellow flex-shrink-0 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-[10px] text-gray-500 uppercase tracking-tight leading-relaxed">
                            {{ __('terms.riot_notice') }}
                        </p>
                    </div>
                </div>

            </div>
        </section>
    </div>
@endsection
