{{--
    GC-Stats — Forgot password

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('auth.forgot_password.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-5 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('auth.forgot_password.title') }}
                </h1>
                <p class="text-sm text-gray-400 mt-2">{{ __('auth.forgot_password.subtitle') }}</p>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">
                @if (session('status'))
                    <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('auth.forgot_password.email_label') }}
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('email')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('auth.forgot_password.submit') }}
                    </button>
                </form>

                <p class="text-xs text-gray-500 text-center">
                    <a href="{{ route('login') }}" class="text-gc-yellow font-semibold hover:underline">{{ __('auth.forgot_password.back_to_login') }}</a>
                </p>
            </div>
        </section>
    </div>
@endsection
