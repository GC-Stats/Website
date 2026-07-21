{{--
    GC-Stats — Admin: profile & preferences

    Second edit surface for the signed-in user, scoped to the admin panel:
    basic profile fields (name/username/email, posted to Fortify's existing
    user-profile-information route) plus the site-wide display preferences
    (theme, accent, language, timezone, time format) — otherwise only
    reachable from the public site's nav gear-icon panel. Preferences are
    stored client-side, see resources/js/app.js's GCS.* helpers.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.profile.title'))

@section('content')
    <div class="max-w-2xl mx-auto space-y-6">
        {{-- admin.layout already renders session('status') globally --}}
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.profile.profile.title') }}</h2>

            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                        {{ __('admin.profile.profile.name_label') }}
                    </label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    @error('name', 'updateProfileInformation')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="username" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                        {{ __('admin.profile.profile.username_label') }}
                    </label>
                    <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}" required
                           class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    @error('username', 'updateProfileInformation')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                        {{ __('admin.profile.profile.email_label') }}
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email"
                           class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    @error('email', 'updateProfileInformation')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                    {{ __('admin.profile.profile.submit') }}
                </button>
            </form>
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6"
             x-data="{
                theme: 'dark',
                accent: 'none',
                timezone: '',
                timezones: [],
                timeFormat: '24h',
                init() {
                    this.theme = GCS.getTheme();
                    this.accent = GCS.getAccent();
                    this.timezone = GCS.getTimezone();
                    this.timezones = GCS.getTimezones();
                    this.timeFormat = GCS.getTimeFormat();
                },
                selectTheme(value) { this.theme = value; GCS.setTheme(value); },
                selectAccent(value) { this.accent = value; GCS.setAccent(value); },
                selectTimezone(value) { this.timezone = value; GCS.setTimezone(value); },
                selectTimeFormat(value) { this.timeFormat = value; GCS.setTimeFormat(value); },
             }">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.profile.preferences.title') }}</h2>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.profile.preferences.language') }}</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach (config('locales.supported') as $code => $name)
                        <a href="{{ route('lang.switch', $code) }}"
                           @if(app()->getLocale() == $code) aria-current="true" @endif
                           class="flex items-center gap-2 px-3 py-2.5 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition-all {{ app()->getLocale() == $code ? 'border-gc-yellow text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white' }}">
                            <span class="fi fi-{{ $code == 'en' ? 'gb' : ($code == 'zh' ? 'cn' : ($code == 'ko' ? 'kr' : $code)) }} fis rounded-sm w-4 h-3 shrink-0" aria-hidden="true"></span>
                            <span class="truncate">{{ $name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('layout.config.theme.title') }}</p>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="selectTheme('dark')" :aria-pressed="(theme === 'dark').toString()"
                            class="flex flex-col items-center gap-2 px-4 py-3 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition-all"
                            :class="theme === 'dark' ? 'border-gc-yellow text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                        <span class="w-6 h-6 rounded-full bg-[#0b0b0b] border border-white/20"></span>
                        {{ __('layout.config.theme.dark') }}
                    </button>
                    <button type="button" @click="selectTheme('white')" :aria-pressed="(theme === 'white').toString()"
                            class="flex flex-col items-center gap-2 px-4 py-3 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition-all"
                            :class="theme === 'white' ? 'border-gc-yellow text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                        <span class="w-6 h-6 rounded-full bg-[#f4f4f5] border border-black/10"></span>
                        {{ __('layout.config.theme.white') }}
                    </button>
                </div>
            </div>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('layout.config.accent.title') }}</p>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="selectAccent('none')" :aria-pressed="(accent === 'none').toString()"
                            class="flex flex-col items-center gap-2 px-4 py-3 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition-all"
                            :class="accent === 'none' ? 'border-gc-yellow text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                        <span class="w-6 h-6 rounded-full border border-white/20 bg-transparent"></span>
                        {{ __('layout.config.accent.none') }}
                    </button>
                    <button type="button" @click="selectAccent('pride')" :aria-pressed="(accent === 'pride').toString()"
                            class="flex flex-col items-center gap-2 px-4 py-3 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition-all"
                            :class="accent === 'pride' ? 'border-gc-yellow text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                        <span class="w-6 h-6 rounded-full" style="background: linear-gradient(90deg, #e40303, #ff8c00, #ffed00, #008026, #004dff, #750787);"></span>
                        {{ __('layout.config.accent.pride') }}
                    </button>
                </div>
            </div>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('layout.config.timezone.title') }}</p>
                <div class="relative" x-data="{ tzOpen: false }" @click.away="tzOpen = false">
                    <button type="button" @click="tzOpen = !tzOpen" aria-haspopup="listbox" :aria-expanded="tzOpen.toString()"
                            class="w-full h-[42px] flex items-center justify-between gap-2 px-4 rounded-lg bg-white/5 border text-[11px] font-bold uppercase tracking-widest text-white hover:border-gc-yellow/50 transition-all"
                            :class="tzOpen ? 'border-gc-yellow/50' : 'border-white/10'">
                        <span x-text="timezone" class="truncate"></span>
                        <span class="flex-shrink-0 transition-transform" :class="tzOpen ? 'rotate-180' : ''">
                            <x-fas-chevron-down class="w-3 h-3 text-gray-500" aria-hidden="true" />
                        </span>
                    </button>

                    <div x-show="tzOpen" x-transition x-cloak role="listbox"
                         class="absolute left-0 right-0 mt-2 max-h-60 overflow-y-auto rounded-lg bg-bg-card border border-white/10 shadow-xl z-10">
                        <template x-for="tz in timezones" :key="tz">
                            <button type="button" role="option" @click="selectTimezone(tz); tzOpen = false" :aria-selected="(tz === timezone).toString()"
                                    class="w-full text-left px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest hover:bg-white/5 transition-all"
                                    :class="tz === timezone ? 'text-gc-yellow' : 'text-gray-400 hover:text-white'"
                                    x-text="tz"></button>
                        </template>
                    </div>
                </div>
            </div>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('layout.config.time_format.title') }}</p>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="selectTimeFormat('24h')" :aria-pressed="(timeFormat === '24h').toString()"
                            class="flex flex-col items-center gap-2 px-4 py-3 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition-all"
                            :class="timeFormat === '24h' ? 'border-gc-yellow text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                        {{ __('layout.config.time_format.24h') }}
                    </button>
                    <button type="button" @click="selectTimeFormat('12h')" :aria-pressed="(timeFormat === '12h').toString()"
                            class="flex flex-col items-center gap-2 px-4 py-3 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition-all"
                            :class="timeFormat === '12h' ? 'border-gc-yellow text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                        {{ __('layout.config.time_format.12h') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
