{{--
    GC-Stats — API key reveal confirmation page

    Intentionally does not show the key: this page is safe to be fetched by
    link-preview crawlers (Discord, Slack, Teams...). The key is only
    decrypted after an explicit click below, via a POST request.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('api_key_reveal.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6 text-center">
            <h1 class="text-2xl font-black uppercase tracking-tighter text-white">
                {{ __('api_key_reveal.title') }}
            </h1>

            <p class="text-sm text-gray-400 leading-relaxed">
                {{ __('api_key_reveal.confirm_body') }}
            </p>

            <form method="POST" action="{{ route('api-keys.reveal.confirm', $token) }}">
                @csrf
                <button
                    type="submit"
                    class="bg-gc-yellow text-black font-bold uppercase text-xs tracking-widest py-3 px-6 rounded-sm hover:opacity-90 transition"
                >
                    {{ __('api_key_reveal.confirm_button') }}
                </button>
            </form>
        </section>
    </div>
@endsection
