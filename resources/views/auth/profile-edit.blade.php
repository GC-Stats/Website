{{--
    GC-Stats — Profile edit page

    Lets the signed-in user update the parts of their account that show up
    on their public profile: name/username/pronouns/email, avatar, team fan
    pick, bio and social links. Password/2FA/passkeys/danger zone stay on
    the account settings page (see auth/account-edit.blade.php).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('account.profile_edit.title'))

@php
    $socialPlatforms = ['twitter', 'twitch', 'tiktok', 'instagram', 'youtube', 'discord', 'email'];
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-10 lg:col-start-2">
            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('account.profile_edit.title') }}
                </h1>
            </div>

            @php
                $statusKey = match (session('status')) {
                    'profile-information-updated' => 'account.edit.profile.saved',
                    'team-tag-updated' => 'account.edit.team.saved',
                    'bio-updated' => 'account.edit.bio.saved',
                    default => null,
                };
            @endphp

            @if ($statusKey)
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3 mt-6">
                    {{ __($statusKey) }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <div class="space-y-6">
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
                        <label for="pronouns" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('account.edit.profile.pronouns_label') }}
                        </label>
                        <select id="pronouns" name="pronouns"
                                class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @foreach (__('account.edit.profile.pronouns_options') as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('pronouns', $user->pronouns) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('pronouns', 'updateProfileInformation')
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
            </div>

            <div class="space-y-6">
            {{-- Avatar --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.avatar.title') }}</h2>

                <div class="flex items-center gap-4">
                    <x-user-avatar :user="$user" class="w-16 h-16 rounded-lg bg-white/5 border border-border-subtle text-base" />
                    <p class="text-xs text-gray-500">
                        {{ __('account.edit.avatar.body') }}
                        <a href="https://gravatar.com" target="_blank" rel="noopener noreferrer" class="text-gc-yellow hover:underline">
                            {{ __('account.edit.avatar.link_label') }}
                        </a>
                    </p>
                </div>
            </div>

            {{-- Team fan tag --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.team.title') }}</h2>
                <p class="text-xs text-gray-500">{{ __('account.edit.team.body') }}</p>

                <form method="POST" action="{{ route('account.team.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @livewire('team-fan-picker', ['initialTeamId' => $user->team_id, 'initialTeamTag' => $user->team_tag])

                    @error('team_id')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                    @error('team_tag')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('account.edit.team.submit') }}
                    </button>
                </form>
            </div>

            {{-- Bio & social links --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('account.edit.bio.title') }}</h2>
                <p class="text-xs text-gray-500">{{ __('account.edit.bio.body') }}</p>

                @if ($user->isEligibleForBio())
                    <form method="POST" action="{{ route('account.bio.update') }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="bio" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('account.edit.bio.bio_label') }}
                            </label>
                            <textarea id="bio" name="bio" rows="4" maxlength="1000"
                                      class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('bio', $user->bio) }}</textarea>
                            @error('bio')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('account.edit.bio.socials_label') }}
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($socialPlatforms as $platform)
                                    @if ($platform === 'email' && ! $user->hasGlobalRole())
                                        <input type="text" value="{{ __('account.edit.bio.email_staff_only') }}" disabled
                                               class="w-full bg-[#050505]/60 border border-border-subtle rounded-sm px-3 py-2 text-xs text-gray-600 cursor-not-allowed">
                                        <input type="hidden" name="socials[email]" value="{{ old('socials.email', $user->socials['email'] ?? '') }}">
                                    @else
                                        <input type="text" name="socials[{{ $platform }}]"
                                               placeholder="{{ __('account.edit.bio.social.'.$platform) }}"
                                               value="{{ old('socials.'.$platform, $user->socials[$platform] ?? '') }}"
                                               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                                    @endif
                                @endforeach
                            </div>
                            @error('socials.*')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('account.edit.bio.submit') }}
                        </button>
                    </form>
                @else
                    <p class="text-xs text-gray-500">{{ __('account.edit.bio.not_eligible') }}</p>
                @endif
            </div>
            </div>
            </div>
        </section>
    </div>
@endsection
