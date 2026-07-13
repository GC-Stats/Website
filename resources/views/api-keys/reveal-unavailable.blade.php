{{--
    GC-Stats — API key reveal link unavailable

    Shown when a reveal link has already been consumed or has expired.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('api_key_reveal.unavailable_title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6 text-center">
            <h1 class="text-2xl font-black uppercase tracking-tighter text-white">
                {{ __('api_key_reveal.unavailable_title') }}
            </h1>
            <p class="text-sm text-gray-400 leading-relaxed">
                {{ __('api_key_reveal.unavailable_body') }}
            </p>
        </section>
    </div>
@endsection
