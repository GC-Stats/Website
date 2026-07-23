{{--
    GC-Stats — Email verification notice

    Shown to signed-in users whose email isn't verified yet. Password-based
    login is blocked until verification (see FortifyServiceProvider); users
    who also have a social provider linked can still sign in that way.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('auth.verify_email.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-5 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('auth.verify_email.title') }}
                </h1>
                <p class="text-sm text-gray-400 mt-2">{{ __('auth.verify_email.subtitle') }}</p>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">

                @if (session('status') === 'verification-link-sent')
                    <p class="text-xs text-emerald-400 text-center">
                        {{ __('auth.verify_email.sent') }}
                    </p>
                @endif

                <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                    @csrf
                    <p class="text-xs text-gray-500 text-center">{{ __('auth.verify_email.resend_prompt') }}</p>
                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('auth.verify_email.resend_submit') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-transparent border border-border-subtle text-gray-400 hover:text-white hover:border-white/20">
                        {{ __('auth.verify_email.logout') }}
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
