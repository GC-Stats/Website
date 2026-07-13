{{--
    GC-Stats — Error pages layout

    Shared layout for HTTP error pages (401, 403, 404, 419, 429, 500, 503),
    rendering a code, title and message inside the main app layout.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title')
    @yield('title')
@endsection

@section('content')
    <div class="min-h-[70vh] flex flex-col items-center justify-center text-center px-4 relative overflow-hidden">

        <div class="relative z-10 text-center">
            <h1 class="text-4xl md:text-9xl font-black text-white uppercase  leading-none mb-4 inline-block relative">
                <span class="text-[var(--brand-yellow)]">{{ substr($exception->getStatusCode(), 0, 1) }}</span>{{ substr($exception->getStatusCode(), 1) }}
            </h1>

            <h2 class="text-3xl md:text-2xl font-black text-white uppercase tracking-[0.2em] mb-6">
                @yield('message')
            </h2>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mt-12">
                <a href="/" class="px-8 py-4 border border-[#333] text-gray-400 font-black uppercase text-[10px] tracking-[0.2em] hover:bg-white/5 hover:text-white transition-all">
                    Go back home
                </a>
            </div>
        </div>
    </div>
@endsection
