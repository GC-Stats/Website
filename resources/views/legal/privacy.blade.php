{{--
    GC-Stats — Privacy policy page

    Static page containing the site's privacy policy.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('privacy.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('privacy.title') }}
                </h1>
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2">
                    {{ __('privacy.last_updated', ['date' => date('25/04/2026')]) }}
                </p>
            </div>

            <p class="text-sm text-gray-400 leading-relaxed italic text-center px-4">
                {{ __('privacy.intro') }}
            </p>

            <div class="space-y-6 mt-8">

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">01.</span> {{ __('privacy.analytics.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        {{ __('privacy.analytics.text') }}
                    </p>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">02.</span> {{ __('privacy.public_data.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {{ __('privacy.public_data.text') }}
                        </p>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">03.</span> {{ __('privacy.opt_in.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {{ __('privacy.opt_in.text') }}
                        </p>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">03.</span> {{ __('privacy.private_data.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300 leading-relaxed">
                            {{ __('privacy.private_data.text') }}
                        </p>
                        <div class="bg-bg-main border-l-2 border-gc-yellow p-4">
                            <p class="text-xs text-gray-400 italic leading-snug">
                                {{ __('privacy.private_data.discord_usage') }}
                            </p>
                        </div>
                        <div class="bg-bg-main border-l-2 border-gc-yellow p-4">
                            <p class="text-xs text-gray-400 italic leading-snug">
                                {{ __('privacy.private_data.riot_usage') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-8 shadow-2xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">04.</span> {{ __('privacy.data_structure.title') }}
                    </h2>

                    <p class="text-xs text-gray-400 leading-normal">
                        {{ __('privacy.data_structure.text') }}
                    </p>

                    <div class="pt-6 text-center">
                        <a href="{{ route('data') }}" class="inline-block bg-transparent border border-gray-700 hover:border-gc-yellow text-gray-500 hover:text-white text-[10px] font-black uppercase px-6 py-2 transition tracking-widest">
                            {{ __("privacy.data_structure.button") }}
                        </a>
                    </div>

                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase mb-3 tracking-widest italic">
                            {{ __('privacy.retention.title') }}
                        </h3>
                        <p class="text-xs text-gray-400 leading-normal">
                            {{ __('privacy.retention.text') }}
                        </p>
                    </div>

                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase mb-3 tracking-widest italic">
                            {{ __('privacy.rights.title') }}
                        </h3>
                        <p class="text-xs text-gray-400 leading-normal mb-4">
                            {{ __('privacy.rights.text') }}
                        </p>
                        <p class="text-[10px] font-bold text-white uppercase p-2 bg-bg-main border border-white/5 inline-block">
                            {{ __('privacy.rights.contact') }}
                        </p>
                    </div>
                </div>

                <div class="bg-bg-main border border-border-subtle rounded-sm p-6">
                    <div class="flex items-center justify-center gap-6">
                        <div class="text-gc-yellow flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-center md:text-left">
                            <h4 class="text-[10px] font-black text-white uppercase">{{ __('privacy.cookies.title') }}</h4>
                            <p class="text-[10px] text-gray-500 uppercase tracking-tight">{{ __('privacy.cookies.text') }}</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 text-center">
                    <a href="{{ route('takedown') }}" class="inline-block bg-bg-main border border-white/5 hover:border-gc-yellow text-gray-500 hover:text-white text-[10px] font-black uppercase px-6 py-2 transition tracking-widest">
                        {{ __('privacy.takedown') }}
                    </a>
                </div>

            </div>
        </section>
    </div>
@endsection
