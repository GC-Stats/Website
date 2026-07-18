{{--
    GC-Stats — Login page

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('auth.login.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-5 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('auth.login.title') }}
                </h1>
                <p class="text-sm text-gray-400 mt-2">{{ __('auth.login.subtitle') }}</p>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">

                <div class="grid grid-cols-1 gap-3">
                    <a href="{{ route('social.redirect', 'discord') }}"
                       class="flex items-center justify-center gap-3 w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-[#5865F2] text-white hover:opacity-90">
                        <x-fab-discord class="w-4 h-4" aria-hidden="true" />
                        {{ __('auth.login.social.discord') }}
                    </a>
                    <a href="{{ route('social.redirect', 'twitch') }}"
                       class="flex items-center justify-center gap-3 w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-[#9146FF] text-white hover:opacity-90">
                        <x-fab-twitch class="w-4 h-4" aria-hidden="true" />
                        {{ __('auth.login.social.twitch') }}
                    </a>
                    <a href="{{ route('social.redirect', 'twitter') }}"
                       class="flex items-center justify-center gap-3 w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-black text-white border border-white/10 hover:opacity-90">
                        <x-fab-twitter class="w-4 h-4" aria-hidden="true" />
                        {{ __('auth.login.social.twitter') }}
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="h-[1px] flex-1 bg-white/10"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ __('auth.login.or_divider') }}</span>
                    <div class="h-[1px] flex-1 bg-white/10"></div>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('auth.login.email_label') }}
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('email')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('auth.login.password_label') }}
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('password')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-xs text-gray-400">
                        <input type="checkbox" name="remember" class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                        {{ __('auth.login.remember_me') }}
                    </label>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('auth.login.submit') }}
                    </button>
                </form>

                <p class="text-xs text-gray-500 italic leading-relaxed text-center">
                    {{ __('auth.login.no_account') }}
                </p>

                <p class="text-xs text-gray-500 text-center">
                    {{ __('auth.login.register_prompt') }}
                    <a href="{{ route('register') }}" class="text-gc-yellow font-semibold hover:underline">{{ __('auth.login.register_link') }}</a>
                </p>
            </div>
        </section>
    </div>
@endsection
