{{--
    GC-Stats — API key reveal page

    Single-use page: shows a freshly generated API key exactly once.
    Reloading or revisiting this URL will not show the key again.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('api_key_reveal.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('api_key_reveal.title') }}
                </h1>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <p class="text-sm text-gray-400">
                    <span class="text-gray-500">{{ __('api_key_reveal.client_name') }}:</span>
                    <span class="text-white font-semibold">{{ $clientName }}</span>
                </p>

                <div class="bg-[#050505] p-4 border-t-2 border-gc-yellow">
                    <code id="api-key-value" class="text-gc-yellow break-all text-sm">{{ $apiKey }}</code>
                </div>

                <button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="
                        navigator.clipboard.writeText(document.getElementById('api-key-value').textContent);
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    x-text="copied ? '{{ __('api_key_reveal.copied') }}' : '{{ __('api_key_reveal.copy') }}'"
                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95"
                    :class="copied ? 'bg-green-500 text-black' : 'bg-gc-yellow text-black hover:opacity-90'"
                >
                    {{ __('api_key_reveal.copy') }}
                </button>

                <p class="text-xs text-gray-500 italic leading-relaxed">
                    {{ __('api_key_reveal.warning') }}
                </p>
            </div>
        </section>
    </div>
@endsection
