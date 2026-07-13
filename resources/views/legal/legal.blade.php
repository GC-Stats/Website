{{--
    GC-Stats — Legal notice page

    Static page containing the site's legal notice/mentions légales.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('legal.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">
            <div class="pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('legal.title') }}
                </h1>
                <p class="text-[10px] font-bold text-gray-500 uppercase mt-2">
                    {{ __('legal.last_updated', ['date' => date('25/04/2026')]) }}
                </p>
            </div>


            <div class="space-y-6">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">01.</span> {{ __('legal.editor.title') }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-500">{{ __('legal.editor.identity') }}</p>
                            <p class="font-medium text-white">Alice Alleman</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-500">{{ __('legal.editor.status') }}</p>
                            <p class="font-medium text-white">{{ __("legal.editor.status_value") }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-500">{{ __('legal.editor.email') }}</p>
                            <p class="font-medium text-white underline decoration-gc-yellow">contact@gc-stats.app</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-500">{{ __('legal.editor.director') }}</p>
                            <p class="font-medium text-white">Alice Alleman</p>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">02.</span> {{ __('legal.property.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300">{{ __('legal.property.main_text') }}</p>

                        <div class="bg-bg-main border-l-4 border-gc-yellow p-6 space-y-3">
                            <h3 class="text-xs font-black text-white uppercase italic">{{ __('legal.property.note_title') }}</h3>
                            <p class="text-xs text-gray-400 leading-normal">{{ __('legal.property.note_body') }}</p>
                            <p class="text-xs text-gray-300 font-bold pt-2">{{ __('legal.property.disclaimer') }}</p>

                            <div class="pt-2">
                                <a href="{{ route('takedown') }}" class="inline-block bg-gc-yellow hover:bg-yellow-400 text-black text-[10px] font-black uppercase px-6 py-2 rounded-sm transition">
                                    {{ __('legal.property.takedown_btn') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">03.</span> {{ __('legal.data_usage.title') }}
                    </h2>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-300">{{ __('legal.data_usage.main_text') }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-[#050505] p-4 border-t-2 border-gc-yellow">
                                <p class="text-[10px] uppercase font-bold text-gray-500 mb-2">{{ __('legal.data_usage.allowed_title') }}</p>
                                <p class="text-xs text-gray-300 leading-relaxed">{{ __('legal.data_usage.allowed_text') }}</p>
                            </div>
                            <div class="bg-[#050505] p-4 border-t-2 border-red-600">
                                <p class="text-[10px] uppercase font-bold text-red-500 mb-2">{{ __('legal.data_usage.forbidden_title') }}</p>
                                <p class="text-xs text-gray-300 leading-relaxed">{{ __('legal.data_usage.forbidden_text') }}</p>
                            </div>
                        </div>

                        <p class="text-[10px] text-gray-500 italic mt-4">
                            {{ __('legal.data_usage.attribution_notice') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                        <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                            <span class="text-gc-yellow">04.</span> {{ __('legal.hosting.title') }}
                        </h2>
                        <div class="text-sm space-y-3 text-gray-300">
                            <p><span class="text-[10px] block font-bold text-gray-500 uppercase">{{ __('legal.hosting.name') }}</span>Hetzner Online GmbH Industriestr.</p>
                            <p><span class="text-[10px] block font-bold text-gray-500 uppercase">{{ __('legal.hosting.address') }}</span>25 91710 Gunzenhausen, Germany</p>
                        </div>
                    </div>

                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                        <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                            <span class="text-gc-yellow">05.</span> {{ __('legal.gdpr.title') }}
                        </h2>
                        <div class="text-sm space-y-3 text-gray-300">
                            <p>{{ __('legal.gdpr.intro') }}</p>
                            <p><span class="text-[10px] block font-bold text-gray-500 uppercase">{{ __('legal.gdpr.contact') }}</span>gpdr@gc-stats.app</p>
                        </div>
                    </div>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">06.</span> {{ __('legal.cookies.title') }}
                    </h2>
                    <p class="text-xs text-gray-400">{{ __('legal.cookies.text') }}</p>
                </div>
            </div>
        </section>
    </div>
@endsection
