{{--
    GC-Stats — Password confirmation page

    Shown before sensitive account actions (enabling 2FA, managing passkeys)
    to re-verify the currently signed-in user's identity.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('auth.confirm_password.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-5 lg:col-start-4 space-y-6">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('auth.confirm_password.title') }}
                </h1>
                <p class="text-sm text-gray-400 mt-2">{{ __('auth.confirm_password.subtitle') }}</p>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="password" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('auth.confirm_password.password_label') }}
                        </label>
                        <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('password')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('auth.confirm_password.submit') }}
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
