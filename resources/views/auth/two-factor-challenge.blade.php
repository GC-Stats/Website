{{--
    GC-Stats — Two-factor login challenge

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('auth.two_factor_challenge.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-5 lg:col-start-4 space-y-6" x-data="{ useRecovery: false }">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('auth.two_factor_challenge.title') }}
                </h1>
                <p class="text-sm text-gray-400 mt-2" x-show="!useRecovery">{{ __('auth.two_factor_challenge.subtitle') }}</p>
                <p class="text-sm text-gray-400 mt-2" x-show="useRecovery" x-cloak>{{ __('auth.two_factor_challenge.recovery_subtitle') }}</p>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
                    @csrf

                    <div x-show="!useRecovery">
                        <label for="code" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('auth.two_factor_challenge.code_label') }}
                        </label>
                        <input id="code" :name="useRecovery ? '' : 'code'" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus
                               x-bind:required="!useRecovery"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition tracking-[0.3em] text-center">
                        @error('code')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="useRecovery" x-cloak>
                        <label for="recovery_code" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('auth.two_factor_challenge.recovery_code_label') }}
                        </label>
                        <input id="recovery_code" :name="useRecovery ? 'recovery_code' : ''" type="text" autocomplete="one-time-code"
                               x-bind:required="useRecovery"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('recovery_code')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('auth.two_factor_challenge.submit') }}
                    </button>

                    <button type="button" @click="useRecovery = !useRecovery"
                            class="w-full text-center text-xs text-gray-500 hover:text-white transition">
                        <span x-show="!useRecovery">{{ __('auth.two_factor_challenge.toggle_to_recovery') }}</span>
                        <span x-show="useRecovery" x-cloak>{{ __('auth.two_factor_challenge.toggle_to_code') }}</span>
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
