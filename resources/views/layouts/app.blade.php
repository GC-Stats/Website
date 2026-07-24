{{--
    GC-Stats — Main layout

    Base HTML layout shared by all pages: head/meta tags, fonts, navigation,
    footer, and the @yield('content') section filled in by child views.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (function () {
            if (localStorage.getItem('gcs_theme') === '1') {
                document.documentElement.setAttribute('data-theme', 'white');
            }

            if (localStorage.getItem('gcs_accent') === '1') {
                document.documentElement.setAttribute('data-accent', 'pride');
            }
        })();
    </script>

    <title>@yield('title', '') | {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', __('layout.meta.default_description'))">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', '') | {{ config('app.name') }}">
    <meta property="og:description" content="@yield('description', __('layout.meta.default_description'))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:image" content="@yield('og_image', asset('web-app-manifest-512x512.png'))">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@GC_Stats">
    <meta name="twitter:title" content="@yield('title', '') | {{ config('app.name') }}">
    <meta name="twitter:description" content="@yield('description', __('layout.meta.default_description'))">
    <meta name="twitter:image" content="@yield('og_image', asset('web-app-manifest-512x512.png'))">

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <meta name="apple-mobile-web-app-title" content="GC Stats" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('schema')
</head>

<body class="flex flex-col min-h-screen">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[9999] focus:px-4 focus:py-2 focus:bg-[var(--brand-yellow)] focus:text-black focus:font-black focus:text-xs focus:uppercase focus:tracking-widest focus:rounded-lg">
        {{ __('layout.skip_to_content') }}
    </a>

    <x-verify-email-banner />

    @php
        $locales = collect(config('locales.supported'))->mapWithKeys(function ($name, $code) {
            return [$code => [
                'name' => $name,
                'flag' => ($code === 'en') ? 'gb' : (($code === 'zh') ? 'cn' : (($code === 'ko') ? 'kr' : $code))
            ]];
        });

        $currentCode = app()->getLocale();
        $current = $locales->get($currentCode, ['name' => 'Unknown', 'flag' => 'un']);
    @endphp

    <nav x-data="{
            mobileMenuOpen: false,
            searchOpen: false,
            configOpen: false,
            theme: 'dark',
            accent: 'none',
            timezone: '',
            timezones: [],
            timeFormat: '24h',
            initConfig() {
                this.theme = GCS.getTheme();
                this.accent = GCS.getAccent();
                this.timezone = GCS.getTimezone();
                this.timezones = GCS.getTimezones();
                this.timeFormat = GCS.getTimeFormat();
            },
            selectTheme(value) {
                this.theme = value;
                GCS.setTheme(value);
            },
            selectAccent(value) {
                this.accent = value;
                GCS.setAccent(value);
            },
            selectTimezone(value) {
                this.timezone = value;
                GCS.setTimezone(value);
            },
            selectTimeFormat(value) {
                this.timeFormat = value;
                GCS.setTimeFormat(value);
            },
         }"
         x-init="initConfig()"
         aria-label="{{ __('layout.nav.aria_label') }}"
         class="sticky top-4 z-50 w-[calc(100%-2rem)] max-w-7xl mx-auto bg-black/40 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_8px_32px_rgba(0,0,0,0.8)]">
        <div class="px-4 md:px-6">
            <div class="flex items-center justify-between h-16">

                <div class="flex items-center gap-6">
                    <a href="/" class="brand-logo group flex items-center shrink-0">
                        <span class="text-xl md:text-2xl font-black tracking-tighter text-white uppercase italic transition-all group-hover:drop-shadow-[0_0_8px_var(--brand-yellow)]">
                            GC<span class="gc-stats-text text-[var(--brand-yellow)]"><span class="gc-letter">S</span><span class="gc-letter">T</span><span class="gc-letter">A</span><span class="gc-letter">T</span><span class="gc-letter">S</span></span>
                        </span>
                        <span class="pride-flag-badge" aria-hidden="true"></span>
                    </a>

                    <div class="hidden md:flex items-center gap-2">
                        <a href="{{ route('home') }}"
                           @if(request()->routeIs('home')) aria-current="page" @endif
                           class="relative px-5 py-2 text-xs font-bold uppercase tracking-widest transition-all rounded-lg
                                {{ request()->routeIs('home') ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                            <x-fas-house-chimney class="w-3.5 h-3.5 inline-block mr-2 {{ request()->routeIs('home') ? 'text-black' : 'text-[var(--brand-yellow)]' }}" aria-hidden="true" />
                            {{ __('index.title') }}
                        </a>
                        <a href="{{ route('tournaments.index') }}"
                           @if(request()->routeIs('tournaments.index')) aria-current="page" @endif
                           class="relative px-5 py-2 text-xs font-bold uppercase tracking-widest transition-all rounded-lg
                                {{ request()->routeIs('tournaments.index') ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                            <x-fas-trophy class="w-3.5 h-3.5 inline-block mr-2 {{ request()->routeIs('tournaments.index') ? 'text-black' : 'text-[var(--brand-yellow)]' }}" aria-hidden="true" />
                            {{ __('tournament.title.nav') }}
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 md:gap-4 flex-1">

                    <div class="hidden md:block w-full max-w-md">
                        @livewire("search.global")
                    </div>

                    <div class="hidden md:block h-6 w-[1px] bg-white/10 ml-2"></div>

                    @auth
                        <div class="relative"
                             x-data="{ accountOpen: false }"
                             @click.away="accountOpen = false">
                            <button
                                @click="accountOpen = !accountOpen"
                                aria-haspopup="true"
                                :aria-expanded="accountOpen.toString()"
                                aria-label="{{ __('layout.account.menu_label') }}"
                                class="flex-shrink-0 w-9 h-9 rounded-xl bg-white/5 border border-white/10 hover:border-[var(--brand-yellow)]/50 transition-all">
                                <x-user-avatar :user="auth()->user()" class="w-full h-full rounded-xl text-[10px]" />
                            </button>

                            <div x-show="accountOpen"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 role="menu"
                                 class="absolute right-0 mt-2 w-52 bg-bg-main/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.7)] z-50 overflow-hidden origin-top-right"
                                 x-cloak>
                                <div class="py-1">
                                    <a href="{{ route('users.show', auth()->user()->username) }}" role="menuitem"
                                       class="flex items-center gap-3 px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                                        <x-fas-id-card class="w-3.5 h-3.5" aria-hidden="true" />
                                        {{ __('layout.account.profile') }}
                                    </a>
                                    <a href="{{ route('account.edit') }}" role="menuitem"
                                       class="flex items-center gap-3 px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                                        <x-fas-user class="w-3.5 h-3.5" aria-hidden="true" />
                                        {{ __('layout.account.settings') }}
                                    </a>
                                    @can('access-admin')
                                        <a href="{{ route('admin.dashboard') }}" role="menuitem"
                                           class="flex items-center gap-3 px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                                            <x-fas-shield-halved class="w-3.5 h-3.5" aria-hidden="true" />
                                            {{ __('layout.account.admin') }}
                                        </a>
                                    @endcan
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" role="menuitem"
                                                class="w-full flex items-center gap-3 px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                                            <x-fas-arrow-right-from-bracket class="w-3.5 h-3.5" aria-hidden="true" />
                                            {{ __('layout.account.logout') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}"
                           class="flex-shrink-0 flex items-center px-4 py-2 text-[10px] font-bold uppercase tracking-widest rounded-xl bg-white/5 border border-white/10 text-gray-300 hover:border-[var(--brand-yellow)]/50 hover:text-white transition-all">
                            {{ __('layout.account.login') }}
                        </a>
                    @endauth

                    <div class="relative"
                         x-data="{ langOpen: false }"
                         @mouseenter="langOpen = true"
                         @mouseleave="langOpen = false"
                         @click.away="langOpen = false">

                        <button
                            @click="langOpen = true"
                            aria-expanded="false"
                            :aria-expanded="langOpen.toString()"
                            aria-haspopup="true"
                            aria-label="{{ __('layout.lang.switcher_label') }}"
                            class="flex-shrink-0 flex items-center justify-center p-2 rounded-xl bg-transparent border border-white/10 hover:border-[var(--brand-yellow)]/50 hover:bg-white/5 transition-all group"
                            :class="langOpen ? 'border-[var(--brand-yellow)]/50 bg-white/5' : ''">

                            @php
                                $current = app()->getLocale();
                                $flag = ($current == 'en' ? 'gb' : ($current == 'zh' ? 'cn' : ($current == 'ko' ? 'kr' : $current)));
                            @endphp

                            <div class="relative flex items-center justify-center w-6 h-4">
                                <span class="fi fi-{{ $flag }} fis rounded-sm w-full h-full block shadow-sm transition-transform duration-300"
                                      :class="langOpen ? 'scale-110' : 'group-hover:scale-110'"></span>
                            </div>
                        </button>

                        <div
                            x-show="langOpen"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            role="menu"
                            aria-label="{{ __('layout.lang.dropdown_label') }}"
                            class="absolute right-0 mt-2 w-48 bg-bg-main/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.7)] z-50 overflow-hidden origin-top-right"
                            x-cloak>

                            <div class="py-1">
                                @foreach(config('locales.supported') as $code => $name)
                                    <a href="{{ route('lang.switch', $code) }}"
                                       role="menuitem"
                                       @if(app()->getLocale() == $code) aria-current="true" @endif
                                       class="flex items-center justify-between px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-white/5 hover:text-white transition-all group/item">
                                        <div class="flex items-center gap-3">
                                            <span class="fi fi-{{ $code == 'en' ? 'gb' : ($code == 'zh' ? 'cn' : ($code == 'ko' ? 'kr' : $code)) }} fis rounded-sm w-4 h-3 opacity-70 group-hover/item:opacity-100 transition-opacity" aria-hidden="true"></span>
                                            {{ $name }}
                                        </div>

                                        @if(app()->getLocale() == $code)
                                            <div class="w-1 h-1 rounded-full bg-[var(--brand-yellow)] shadow-[0_0_5px_var(--brand-yellow)]" aria-hidden="true"></div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <button
                        @click="configOpen = true"
                        aria-haspopup="dialog"
                        :aria-expanded="configOpen.toString()"
                        aria-controls="config-panel"
                        aria-label="{{ __('layout.config.button_label') }}"
                        class="flex-shrink-0 flex items-center justify-center p-2 rounded-xl bg-transparent border border-white/10 hover:border-[var(--brand-yellow)]/50 hover:bg-white/5 transition-all group">
                        <x-fas-gear class="w-4 h-4 text-gray-400 group-hover:text-white transition-all" aria-hidden="true" />
                    </button>

                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            aria-expanded="false"
                            :aria-expanded="mobileMenuOpen.toString()"
                            aria-controls="mobile-menu"
                            :aria-label="mobileMenuOpen ? '{{ __('layout.nav.close_menu') }}' : '{{ __('layout.nav.open_menu') }}'"
                            class="md:hidden flex items-center justify-center p-2 bg-bg-main/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.7)] z-50 overflow-hidden origin-top-right">

                        <template x-if="mobileMenuOpen">
                            @svg('fas-xmark', 'w-4 h-4 inline-block', ['aria-hidden' => 'true'])
                        </template>

                        <template x-if="!mobileMenuOpen">
                            @svg('fas-magnifying-glass', 'w-4 h-4 inline-block', ['aria-hidden' => 'true'])
                        </template>

                    </button>
                </div>
            </div>
        </div>

        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             @click.away="mobileMenuOpen = false"
             x-cloak
             id="mobile-menu"
             class="md:hidden px-4 pb-6 border-t border-white/5 bg-bg-main/95 backdrop-blur-3xl rounded-b-2xl">

            <div class="pt-6">
                <p class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ __('Search') }}</p>
                @livewire("search.global")
            </div>

            <div class="mt-6 flex flex-col gap-2">
                <p class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-1 ml-1">{{ __('Menu') }}</p>
                <a href="{{ route('home') }}"
                   @if(request()->routeIs('home')) aria-current="page" @endif
                   class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all {{ request()->routeIs('home') ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 bg-white/5' }}">
                    <x-fas-house-chimney class="w-3.5 h-3.5" aria-hidden="true" />
                    {{ __('index.title') }}
                </a>
                <a href="{{ route('tournaments.index') }}"
                   @if(request()->routeIs('tournaments.index')) aria-current="page" @endif
                   class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all {{ request()->routeIs('tournaments.index') ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 bg-white/5' }}">
                    <x-fas-trophy class="w-3.5 h-3.5" aria-hidden="true" />
                    {{ __('tournament.title.nav') }}
                </a>

                @auth
                    <a href="{{ route('account.edit') }}"
                       @if(request()->routeIs('account.edit')) aria-current="page" @endif
                       class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all {{ request()->routeIs('account.edit') ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 bg-white/5' }}">
                        <x-fas-user class="w-3.5 h-3.5" aria-hidden="true" />
                        {{ __('layout.account.settings') }}
                    </a>
                    @can('access-admin')
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all text-gray-400 bg-white/5">
                            <x-fas-shield-halved class="w-3.5 h-3.5" aria-hidden="true" />
                            {{ __('layout.account.admin') }}
                        </a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all text-gray-400 bg-white/5">
                            <x-fas-arrow-right-from-bracket class="w-3.5 h-3.5" aria-hidden="true" />
                            {{ __('layout.account.logout') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest rounded-xl transition-all text-gray-400 bg-white/5">
                        <x-fas-user class="w-3.5 h-3.5" aria-hidden="true" />
                        {{ __('layout.account.login') }}
                    </a>
                @endauth
            </div>
        </div>

        <template x-teleport="body">
        <div x-show="configOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="configOpen = false"
             class="fixed inset-0 z-[60] bg-black/60 backdrop-blur-sm"
             x-cloak
             @click="configOpen = false"></div>
        </template>

        <template x-teleport="body">
        <div id="config-panel"
             x-show="configOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             @click.away="configOpen = false"
             x-cloak
             role="dialog"
             aria-modal="true"
             aria-label="{{ __('layout.config.panel_label') }}"
             class="fixed top-0 right-0 z-[70] h-full w-full max-w-sm bg-bg-main border-l border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.8)] overflow-y-auto">

            <div class="flex items-center justify-between px-6 h-16 border-b border-white/5">
                <h2 class="text-xs font-black uppercase tracking-[0.2em] text-white">
                    {{ __('layout.config.title') }}
                </h2>
                <button @click="configOpen = false"
                        aria-label="{{ __('layout.config.close') }}"
                        class="flex items-center justify-center p-2 rounded-xl bg-transparent border border-white/10 hover:border-[var(--brand-yellow)]/50 hover:bg-white/5 transition-all">
                    @svg('fas-xmark', 'w-4 h-4 text-gray-400', ['aria-hidden' => 'true'])
                </button>
            </div>

            <div class="p-6 flex flex-col gap-8">

                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mb-4 flex items-center gap-2">
                        <span class="w-1 h-3 bg-[var(--brand-yellow)]"></span>
                        {{ __('layout.config.theme.title') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="selectTheme('dark')"
                                :aria-pressed="(theme === 'dark').toString()"
                                class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl border text-[10px] font-bold uppercase tracking-widest transition-all"
                                :class="theme === 'dark' ? 'border-[var(--brand-yellow)] text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                            <span class="w-6 h-6 rounded-full bg-[#0b0b0b] border border-white/20"></span>
                            {{ __('layout.config.theme.dark') }}
                        </button>
                        <button @click="selectTheme('white')"
                                :aria-pressed="(theme === 'white').toString()"
                                class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl border text-[10px] font-bold uppercase tracking-widest transition-all"
                                :class="theme === 'white' ? 'border-[var(--brand-yellow)] text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                            <span class="w-6 h-6 rounded-full bg-[#f4f4f5] border border-black/10"></span>
                            {{ __('layout.config.theme.white') }}
                        </button>
                    </div>
                </div>

                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mb-4 flex items-center gap-2">
                        <span class="w-1 h-3 bg-[var(--brand-yellow)]"></span>
                        {{ __('layout.config.accent.title') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="selectAccent('none')"
                                :aria-pressed="(accent === 'none').toString()"
                                class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl border text-[10px] font-bold uppercase tracking-widest transition-all"
                                :class="accent === 'none' ? 'border-[var(--brand-yellow)] text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                            <span class="w-6 h-6 rounded-full border border-white/20 bg-transparent"></span>
                            {{ __('layout.config.accent.none') }}
                        </button>
                        <button @click="selectAccent('pride')"
                                :aria-pressed="(accent === 'pride').toString()"
                                class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl border text-[10px] font-bold uppercase tracking-widest transition-all"
                                :class="accent === 'pride' ? 'border-[var(--brand-yellow)] text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                            <span class="w-6 h-6 rounded-full" style="background: linear-gradient(90deg, #e40303, #ff8c00, #ffed00, #008026, #004dff, #750787);"></span>
                            {{ __('layout.config.accent.pride') }}
                        </button>
                    </div>
                </div>

                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mb-4 flex items-center gap-2">
                        <span class="w-1 h-3 bg-[var(--brand-yellow)]"></span>
                        {{ __('layout.config.timezone.title') }}
                    </h3>
                    <div class="relative" x-data="{ tzOpen: false }" @click.away="tzOpen = false">
                        <button type="button"
                                id="config-timezone"
                                @click="tzOpen = !tzOpen"
                                aria-haspopup="listbox"
                                :aria-expanded="tzOpen.toString()"
                                class="w-full flex items-center justify-between gap-2 px-4 py-3 rounded-xl bg-white/5 border text-[11px] font-bold uppercase tracking-widest text-white hover:border-[var(--brand-yellow)]/50 transition-all"
                                :class="tzOpen ? 'border-[var(--brand-yellow)]/50' : 'border-white/10'">
                            <span x-text="timezone" class="truncate"></span>
                            <span class="flex-shrink-0 transition-transform" :class="tzOpen ? 'rotate-180' : ''">
                                <x-fas-chevron-down class="w-3 h-3 text-gray-500" aria-hidden="true" />
                            </span>
                        </button>

                        <div x-show="tzOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             role="listbox"
                             aria-label="{{ __('layout.config.timezone.title') }}"
                             class="absolute left-0 right-0 mt-2 max-h-60 overflow-y-auto rounded-xl bg-bg-card border border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.7)] z-10"
                             x-cloak>
                            <template x-for="tz in timezones" :key="tz">
                                <button type="button"
                                        role="option"
                                        @click="selectTimezone(tz); tzOpen = false"
                                        :aria-selected="(tz === timezone).toString()"
                                        class="w-full text-left px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest hover:bg-white/5 transition-all"
                                        :class="tz === timezone ? 'text-[var(--brand-yellow)]' : 'text-gray-400 hover:text-white'"
                                        x-text="tz"></button>
                            </template>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-500 mt-3 leading-relaxed">
                        {{ __('layout.config.timezone.description') }}
                    </p>
                </div>

                <div>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mb-4 flex items-center gap-2">
                        <span class="w-1 h-3 bg-[var(--brand-yellow)]"></span>
                        {{ __('layout.config.time_format.title') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="selectTimeFormat('24h')"
                                :aria-pressed="(timeFormat === '24h').toString()"
                                class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl border text-[10px] font-bold uppercase tracking-widest transition-all"
                                :class="timeFormat === '24h' ? 'border-[var(--brand-yellow)] text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                            {{ __('layout.config.time_format.24h') }}
                        </button>
                        <button @click="selectTimeFormat('12h')"
                                :aria-pressed="(timeFormat === '12h').toString()"
                                class="flex flex-col items-center gap-2 px-4 py-3 rounded-xl border text-[10px] font-bold uppercase tracking-widest transition-all"
                                :class="timeFormat === '12h' ? 'border-[var(--brand-yellow)] text-white bg-white/5' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white'">
                            {{ __('layout.config.time_format.12h') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
        </template>
    </nav>

    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-[var(--brand-yellow)] opacity-[0.03] blur-[120px]"></div>
        <div class="absolute top-[20%] -right-[5%] w-[30%] h-[30%] rounded-full bg-[var(--brand-yellow)] opacity-[0.02] blur-[100px]"></div>
    </div>

    <main id="main-content" tabindex="-1" class="flex-grow pt-20 px-4 md:px-8 w-full max-w-[1920px] mx-auto">
        @if(isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    <footer role="contentinfo" class="relative bg-[#050505] border-t border-white/5 pt-20 pb-10 mt-20 overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-[1px] bg-gradient-to-r from-transparent via-[var(--brand-yellow)]/20 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 md:gap-8 mb-16 items-start">

                <div class="flex flex-col items-center md:items-start">
                    <a href="/" class="brand-logo group flex items-center mb-6">
                        <span class="text-2xl font-black tracking-tighter text-white uppercase italic">
                            GC<span class="gc-stats-text text-[var(--brand-yellow)] group-hover:drop-shadow-[0_0_8px_var(--brand-yellow)] transition-all"><span class="gc-letter">S</span><span class="gc-letter">T</span><span class="gc-letter">A</span><span class="gc-letter">T</span><span class="gc-letter">S</span></span>
                        </span>
                    </a>
                    <p class="text-gray-500 text-[11px] leading-relaxed max-w-[200px] text-center md:text-left font-bold tracking-wider">
                        {{ __("layout.footer.description") }}
                    </p>
                </div>

                <div class="flex flex-col items-center md:items-start">
                    <h4 class="h-8 flex items-center text-[10px] font-black uppercase tracking-[0.3em] text-white/90 mb-4 gap-2">
                        <span class="w-1 h-3 bg-[var(--brand-yellow)]"></span>
                        {{ __("layout.footer.about.title") }}
                    </h4>
                    <ul class="space-y-4 w-full">
                        <li>
                            <a href="{{ route('about') }}" class="text-[11px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all flex items-center justify-center md:justify-start gap-2 group">
                                <span class="w-0 h-[1px] bg-[var(--brand-yellow)] transition-all group-hover:w-3"></span>
                                {{ __("layout.footer.about.about_us") }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('takedown') }}" class="text-[11px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all flex items-center justify-center md:justify-start gap-2 group">
                                <span class="w-0 h-[1px] bg-[var(--brand-yellow)] transition-all group-hover:w-3"></span>
                                {{ __("layout.footer.about.dmca") }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('developers') }}" class="text-[11px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all flex items-center justify-center md:justify-start gap-2 group">
                                <span class="w-0 h-[1px] bg-[var(--brand-yellow)] transition-all group-hover:w-3"></span>
                                {{ __("layout.footer.about.dev_doc") }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col items-center md:items-start">
                    <h4 class="h-8 flex items-center text-[10px] font-black uppercase tracking-[0.3em] text-white/90 mb-4 gap-2">
                        <span class="w-1 h-3 bg-[var(--brand-yellow)]"></span>
                        {{ __("layout.footer.informations.title") }}
                    </h4>
                    <ul class="space-y-4 w-full">
                        <li>
                            <a href="{{ route('transparency') }}" class="text-[11px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all flex items-center justify-center md:justify-start gap-2 group">
                                <span class="w-0 h-[1px] bg-[var(--brand-yellow)] transition-all group-hover:w-3"></span>
                                {{ __("layout.footer.about.transparency") }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('help.edit_page') }}" class="text-[11px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all flex items-center justify-center md:justify-start gap-2 group">
                                <span class="w-0 h-[1px] bg-[var(--brand-yellow)] transition-all group-hover:w-3"></span>
                                {{ __("layout.footer.informations.update_page") }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('help.add_tournament') }}" class="text-[11px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all flex items-center justify-center md:justify-start gap-2 group">
                                <span class="w-0 h-[1px] bg-[var(--brand-yellow)] transition-all group-hover:w-3"></span>
                                {{ __("layout.footer.informations.add_tournament") }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col items-center md:items-start">
                    <h4 class="h-8 flex items-center text-[10px] font-black uppercase tracking-[0.3em] text-white/90 mb-4">
                        {{ __("layout.footer.socials") }}
                    </h4>
                    <div class="flex gap-3">
                        <a href="https://x.com/GC_Stats" target="_blank" rel="noopener noreferrer"
                           aria-label="Twitter / X"
                           class="w-11 h-11 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-white transition-all duration-300 hover:bg-[#1DA1F2] hover:-translate-y-1 hover:shadow-[0_10px_20px_rgba(0,0,0,0.4)]">
                            <x-fab-twitter class="w-4 h-4" aria-hidden="true" />
                        </a>
                        <a href="https://discord.gg/JZgVmAFK9a" target="_blank" rel="noopener noreferrer"
                           aria-label="Discord"
                           class="w-11 h-11 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-white transition-all duration-300 hover:bg-[#5865F2] hover:-translate-y-1 hover:shadow-[0_10px_20px_rgba(0,0,0,0.4)]">
                            <x-fab-discord class="w-4 h-4" aria-hidden="true" />
                        </a>
                        <a href="https://github.com/GC-Stats/" target="_blank" rel="noopener noreferrer"
                           aria-label="Github"
                           class="w-11 h-11 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-white transition-all duration-300 hover:bg-white hover:text-black hover:-translate-y-1 hover:shadow-[0_10px_20px_rgba(0,0,0,0.4)]">
                            <x-fab-github class="w-4 h-4" aria-hidden="true" />
                        </a>
                    </div>
                </div>
        </div>

            <div class="pt-10 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-8">
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-[0.2em] italic">
                    {{ __("layout.footer.copyright", ["year" => date('Y'), "name" => 'GC Stats']) }}
                    <span class="text-gray-500/50 mx-1">/</span>
                    <span class="text-gray-500 font-black not-italic">v {{ config('app.version') }}</span>
                </p>

                <div class="flex gap-8 items-center">
                    <a href="{{ route('terms') }}" class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] hover:text-white transition-colors">{{ __("layout.footer.terms") }}</a>
                    <div class="w-1 h-1 bg-white/10 rounded-full"></div>
                    <a href="{{ route('legal') }}" class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] hover:text-white transition-colors">{{ __("layout.footer.legal") }}</a>
                    <div class="w-1 h-1 bg-white/10 rounded-full"></div>
                    <a href="{{ route('privacy') }}" class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] hover:text-white transition-colors">{{ __("layout.footer.privacy") }}</a>
                </div>
            </div>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
