{{--
    GC-Stats — Takedown request page

    Static page explaining how rights holders can request the takedown of
    content from GC-Stats.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('takedown.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('takedown.title') }}
                </h1>
            </div>

            <p class="text-sm text-gray-300 leading-relaxed text-center px-4">
                {{ __('takedown.intro') }}
            </p>

            <div class="bg-bg-card border border-border-subtle rounded-sm overflow-hidden shadow-2xl">
                <div class="p-8 space-y-8">

                    <div class="space-y-4">
                        <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                            <span class="text-gc-yellow">/</span> {{ __('takedown.how_to.title') }}
                        </h2>
                        <p class="text-sm text-gray-400">
                            {{ __('takedown.how_to.text') }}
                        </p>
                    </div>

                    <div class="bg-bg-main border border-border-subtle p-6 space-y-3">
                        <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest italic">
                            {{ __('takedown.info_needed.title') }}
                        </h3>
                        <ul class="space-y-2">
                            @foreach(['item1', 'item2', 'item3'] as $item)
                                <li class="flex items-start gap-3 text-xs text-gray-300">
                                    <span class="text-gc-yellow font-bold">—</span>
                                    {{ __('takedown.info_needed.'.$item) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <a href="https://discord.gg/JZgVmAFK9a" target="_blank" class="bg-bg-main border border-border-subtle rounded-sm p-6 hover:border-gc-yellow transition-colors group">
                            <div class="flex items-center gap-6">
                                <div class="text-gc-yellow flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-[10px] font-black text-white uppercase">{{ __('takedown.channels.discord') }}</h4>
                                    <p class="text-[11px] text-gray-500 tracking-tight">https://discord.gg/JZgVmAFK9a</p>
                                </div>
                            </div>
                        </a>

                        <a href="mailto:contact@alicealm.fr" class="bg-bg-main border border-border-subtle rounded-sm p-6 hover:border-gc-yellow transition-colors group">
                            <div class="flex items-center gap-6">
                                <div class="text-gc-yellow flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-[10px] font-black text-white uppercase">{{ __('takedown.channels.email') }}</h4>
                                    <p class="text-[11px] text-gray-500 tracking-tight">takedown@gc-stats.app</p>
                                </div>
                            </div>
                        </a>

                    </div>
                </div>

                <div class="bg-border-subtle/20 py-4 px-8 text-center">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-tight">
                        {{ __('takedown.footer') }}
                    </p>
                </div>
            </div>
        </section>
    </div>
@endsection
