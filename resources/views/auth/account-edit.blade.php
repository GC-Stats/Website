{{--
    GC-Stats — Account settings page

    Lets the signed-in user update their profile, manage their password and
    connected providers, export their data or delete their account.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('account.edit.title'))

@php
    $allProviders = [
        'discord' => ['label' => 'Discord', 'icon' => 'fab-discord', 'color' => '#5865F2'],
        'twitch' => ['label' => 'Twitch', 'icon' => 'fab-twitch', 'color' => '#9146FF'],
        'twitter' => ['label' => 'X', 'icon' => 'fab-twitter', 'color' => '#000000'],
    ];
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6"
                 x-data="accountSecurity({
                    routes: {
                        confirmPassword: '{{ route('password.confirm') }}',
                        twoFactorEnable: '{{ route('two-factor.enable') }}',
                        twoFactorConfirm: '{{ route('two-factor.confirm') }}',
                        twoFactorDisable: '{{ route('two-factor.disable') }}',
                        twoFactorQrCode: '{{ route('two-factor.qr-code') }}',
                        twoFactorSecretKey: '{{ route('two-factor.secret-key') }}',
                        twoFactorRecoveryCodes: '{{ route('two-factor.recovery-codes') }}',
                        passkeyOptions: '{{ route('passkey.registration-options') }}',
                        passkeyStore: '{{ route('passkey.store') }}',
                        passkeyDestroyBase: '{{ url('/user/passkeys') }}',
                    },
                    twoFactorEnabled: {{ $user->hasEnabledTwoFactorAuthentication() ? 'true' : 'false' }},
                    twoFactorPending: {{ (! $user->hasEnabledTwoFactorAuthentication() && $user->two_factor_secret) ? 'true' : 'false' }},
                    passkeys: {{ $user->passkeys->map->only(['id', 'name'])->values()->toJson() }},
                 })">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('account.edit.title') }}
                </h1>
            </div>

            @php
                $statusKey = match (session('status')) {
                    'profile-information-updated' => 'account.edit.profile.saved',
                    'password-updated' => 'account.edit.password.updated',
                    'password-removed' => 'account.edit.password.removed',
                    'provider-unlinked' => 'account.edit.connected.unlinked',
                    default => null,
                };
            @endphp

            @if ($statusKey)
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __($statusKey) }}
                </div>
            @endif

            {{-- Profile --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.profile.title') }}</h2>

                <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('account.edit.profile.name_label') }}
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('name', 'updateProfileInformation')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="username" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('account.edit.profile.username_label') }}
                        </label>
                        <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}" required
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('username', 'updateProfileInformation')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('account.edit.profile.email_label') }}
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        <p class="text-xs text-gray-500 mt-2">{{ __('account.edit.profile.email_help') }}</p>
                        @error('email', 'updateProfileInformation')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('account.edit.profile.submit') }}
                    </button>
                </form>
            </div>

            {{-- Password --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.password.title') }}</h2>

                @if (! $user->password)
                    <p class="text-xs text-gray-500">{{ __('account.edit.password.none_set') }}</p>
                @endif

                <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @if ($user->password)
                        <div>
                            <label for="current_password" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('account.edit.password.current_label') }}
                            </label>
                            <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('current_password')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label for="password" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('account.edit.password.new_label') }}
                        </label>
                        <input id="password" type="password" name="password" autocomplete="new-password"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('password')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('account.edit.password.confirm_label') }}
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ $user->password ? __('account.edit.password.update_submit') : __('account.edit.password.set_submit') }}
                    </button>
                </form>

                @if ($user->password)
                    <form method="POST" action="{{ route('account.password.destroy') }}" class="pt-2 border-t border-border-subtle">
                        @csrf
                        @method('DELETE')

                        <x-confirm-modal
                            :title="__('account.edit.password.title')"
                            :body="__($user->hasEnabledTwoFactorAuthentication() || $user->passkeys->isNotEmpty() ? 'account.edit.password.remove_confirm_with_2fa' : 'account.edit.password.remove_confirm')"
                            :trigger-label="__('account.edit.password.remove_submit')"
                            :submit-label="__('account.edit.password.remove_submit')"
                            class="w-full"
                        >
                            <input type="password" name="current_password" placeholder="{{ __('account.edit.password.current_label') }}" autocomplete="current-password"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('current_password')
                                <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </x-confirm-modal>
                    </form>
                @endif
            </div>

            {{-- Connected accounts --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-3">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.connected.title') }}</h2>

                @foreach ($allProviders as $key => $provider)
                    @php $linked = $user->socialAccounts->firstWhere('provider', $key); @endphp
                    <div class="flex items-center justify-between gap-4 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                        <div class="flex items-center gap-3">
                            @svg($provider['icon'], 'w-4 h-4 text-white', ['aria-hidden' => 'true'])
                            <div>
                                <p class="text-sm font-semibold text-white">{{ $provider['label'] }}</p>
                                @if ($linked)
                                    <p class="text-xs text-gray-500">{{ $linked->nickname }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($linked)
                            <form method="POST" action="{{ route('social.destroy', $linked) }}">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="$provider['label']"
                                    :body="__('account.edit.connected.disconnect_confirm')"
                                    :trigger-label="__('account.edit.connected.disconnect')"
                                    :submit-label="__('account.edit.connected.disconnect')"
                                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        @else
                            <a href="{{ route('social.redirect', $key) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                                {{ __('account.edit.connected.connect') }}
                            </a>
                        @endif
                    </div>
                @endforeach

                @error('social')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Two-factor authentication (password-protected accounts only — it exists to harden a password login) --}}
            @if ($user->password)
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.two_factor.title') }}</h2>

                <template x-if="!twoFactorEnabled && !twoFactorPending">
                    <div class="space-y-4">
                        <p class="text-xs text-gray-500">{{ __('account.edit.two_factor.disabled') }}</p>
                        <button type="button" @click="enableTwoFactor()" :disabled="twoFactorLoading"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 disabled:opacity-50">
                            {{ __('account.edit.two_factor.enable_button') }}
                        </button>
                    </div>
                </template>

                <template x-if="twoFactorPending">
                    <div class="space-y-4" x-cloak>
                        <p class="text-xs text-gray-500">{{ __('account.edit.two_factor.setup_body') }}</p>

                        <div class="bg-white p-4 rounded-sm w-fit mx-auto" x-html="qrSvg"></div>

                        <p class="text-xs text-gray-500 text-center">
                            {{ __('account.edit.two_factor.manual_key') }}
                            <code class="text-gc-yellow" x-text="secretKey"></code>
                        </p>

                        <div>
                            <label for="two_factor_code" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('account.edit.two_factor.code_label') }}
                            </label>
                            <input id="two_factor_code" type="text" inputmode="numeric" autocomplete="one-time-code" x-model="code"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white text-center tracking-[0.3em] focus:outline-none focus:border-gc-yellow transition">
                            <p class="text-xs text-red-400 mt-2" x-show="twoFactorError" x-text="twoFactorError" x-cloak></p>
                        </div>

                        <button type="button" @click="confirmTwoFactorCode()" :disabled="twoFactorLoading"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 disabled:opacity-50">
                            {{ __('account.edit.two_factor.confirm_button') }}
                        </button>
                    </div>
                </template>

                <template x-if="twoFactorEnabled">
                    <div class="space-y-4" x-cloak>
                        <p class="text-xs text-green-400">{{ __('account.edit.two_factor.enabled') }}</p>

                        <div class="pt-2 border-t border-border-subtle space-y-3">
                            <button type="button" @click="toggleRecoveryCodes()"
                                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                <span x-show="!showRecoveryCodes">{{ __('account.edit.two_factor.show_recovery') }}</span>
                                <span x-show="showRecoveryCodes" x-cloak>{{ __('account.edit.two_factor.hide_recovery') }}</span>
                            </button>

                            <div x-show="showRecoveryCodes" x-cloak class="space-y-3">
                                <p class="text-xs text-gray-500">{{ __('account.edit.two_factor.recovery_body') }}</p>
                                <div class="bg-[#050505] border border-border-subtle rounded-sm p-4 grid grid-cols-2 gap-2 font-mono text-xs text-gc-yellow">
                                    <template x-for="recoveryCode in recoveryCodes" :key="recoveryCode">
                                        <span x-text="recoveryCode"></span>
                                    </template>
                                </div>
                                <button type="button" @click="regenerateRecoveryCodes()"
                                        class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                    {{ __('account.edit.two_factor.regenerate_recovery') }}
                                </button>
                            </div>
                        </div>

                        <x-confirm-modal
                            :title="__('account.edit.two_factor.title')"
                            :body="__('account.edit.two_factor.disable_confirm')"
                            :trigger-label="__('account.edit.two_factor.disable_button')"
                            :submit-label="__('account.edit.confirm')"
                            onConfirm="disableTwoFactor()"
                            x-bind:disabled="twoFactorLoading"
                            trigger-class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10 disabled:opacity-50"
                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                        />
                    </div>
                </template>
            </div>

            {{-- Passkeys --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.passkeys.title') }}</h2>
                <p class="text-xs text-gray-500">{{ __('account.edit.passkeys.body') }}</p>

                <template x-if="passkeys.length === 0">
                    <p class="text-xs text-gray-500 italic">{{ __('account.edit.passkeys.empty') }}</p>
                </template>

                <template x-for="passkey in passkeys" :key="passkey.id">
                    <div class="flex items-center justify-between gap-4 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                        <p class="text-sm font-semibold text-white" x-text="passkey.name"></p>
                        <x-confirm-modal
                            :title="__('account.edit.passkeys.remove_button')"
                            :body="__('account.edit.passkeys.remove_confirm')"
                            :trigger-label="__('account.edit.passkeys.remove_button')"
                            :submit-label="__('account.edit.confirm')"
                            onConfirm="deletePasskey(passkey.id)"
                            trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                        />
                    </div>
                </template>

                <div class="pt-2 border-t border-border-subtle space-y-3">
                    <input type="text" x-model="passkeyName" placeholder="{{ __('account.edit.passkeys.name_placeholder') }}"
                           class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    <p class="text-xs text-red-400" x-show="passkeyError" x-text="passkeyError" x-cloak></p>
                    <button type="button" @click="registerPasskey()" :disabled="passkeyLoading"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 disabled:opacity-50">
                        {{ __('account.edit.passkeys.add_button') }}
                    </button>
                </div>
            </div>
            @endif

            {{-- Danger zone --}}
            <div class="bg-bg-card border border-red-500/20 rounded-sm p-6 shadow-xl space-y-6">
                <h2 class="text-xs font-black uppercase tracking-widest text-red-400">{{ __('account.edit.danger.title') }}</h2>

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ __('account.edit.danger.export_title') }}</p>
                        <p class="text-xs text-gray-500">{{ __('account.edit.danger.export_body') }}</p>
                    </div>
                    <a href="{{ route('account.export') }}"
                       class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                        {{ __('account.edit.danger.export_button') }}
                    </a>
                </div>

                <div class="pt-4 border-t border-border-subtle space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ __('account.edit.danger.delete_title') }}</p>
                        <p class="text-xs text-gray-500">{{ __('account.edit.danger.delete_body') }}</p>
                    </div>

                    <form method="POST" action="{{ route('account.destroy') }}">
                        @csrf
                        @method('DELETE')

                        <x-confirm-modal
                            :title="__('account.edit.danger.delete_title')"
                            :body="__('account.edit.danger.delete_confirm')"
                            :trigger-label="__('account.edit.danger.delete_button')"
                            :submit-label="__('account.edit.danger.delete_button')"
                            trigger-class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                        >
                            @if ($user->password)
                                <input type="password" name="current_password" placeholder="{{ __('account.edit.password.current_label') }}" autocomplete="current-password"
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                @error('current_password')
                                    <p class="text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </x-confirm-modal>
                    </form>
                </div>
            </div>

            <template x-teleport="body">
                <div x-show="confirmOpen" x-cloak
                     class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                     @keydown.escape.window="confirmOpen = false">
                    <div @click.away="confirmOpen = false" role="dialog" aria-modal="true"
                         class="w-full max-w-sm bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.confirm_password.title') }}</h2>
                        <p class="text-xs text-gray-500">{{ __('account.edit.confirm_password.body') }}</p>

                        <form @submit.prevent="submitConfirmPassword()" class="space-y-4">
                            <div>
                                <input type="password" x-model="confirmPassword" autocomplete="current-password" autofocus
                                       placeholder="{{ __('account.edit.password.current_label') }}"
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                <p class="text-xs text-red-400 mt-2" x-show="confirmError" x-text="confirmError" x-cloak></p>
                            </div>

                            <div class="flex gap-3">
                                <button type="button" @click="confirmOpen = false; confirmAction = null"
                                        class="flex-1 font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                    {{ __('account.edit.confirm_password.cancel') }}
                                </button>
                                <button type="submit"
                                        class="flex-1 font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                                    {{ __('account.edit.confirm_password.submit') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </section>
    </div>
@endsection
