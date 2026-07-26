{{--
    GC-Stats — Developer dashboard shell

    Standalone layout for /developers: fixed sidebar + top bar, no public-site
    chrome. Add new sections to $navGroups below.

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
    <meta name="robots" content="noindex, nofollow">

    <script>
        (function () {
            if (localStorage.getItem('gcs_theme') === '1') {
                document.documentElement.setAttribute('data-theme', 'white');
            }
        })();
    </script>

    <title>@yield('title', '') | {{ __('developers.dashboard.nav.title') }} | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg-main text-white" x-data="{ sidebarOpen: false }">

    <x-public.verify-email-banner />

    @php
        $canAccessDevelopers = auth()->user()?->can('access-developers') ?? false;
        $defaultApiKey = $canAccessDevelopers ? auth()->user()->apiKeys()->oldest()->first() : null;

        $navGroups = $canAccessDevelopers ? [
            [
                'items' => array_filter([
                    ['route' => 'developers.api-keys.index', 'params' => [], 'pattern' => 'developers.api-keys.*', 'label' => __('developers.dashboard.nav.api-keys'), 'icon' => 'fas-key'],
                    $defaultApiKey ? ['route' => 'developers.requests.index', 'params' => ['key' => $defaultApiKey], 'pattern' => 'developers.requests.*', 'label' => __('developers.dashboard.nav.requests'), 'icon' => 'fas-clock-rotate-left'] : null,
                    $defaultApiKey ? ['route' => 'developers.stats.index', 'params' => ['key' => $defaultApiKey], 'pattern' => 'developers.stats.*', 'label' => __('developers.dashboard.nav.stats'), 'icon' => 'fas-chart-line'] : null,
                ]),
            ],
        ] : [];
    @endphp

    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-[var(--brand-yellow)] opacity-[0.03] blur-[120px]"></div>
        <div class="absolute top-[20%] -right-[5%] w-[30%] h-[30%] rounded-full bg-[var(--brand-yellow)] opacity-[0.02] blur-[100px]"></div>
    </div>

    <div class="flex min-h-screen">
        <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 border-r border-white/10 bg-black/40 backdrop-blur-xl h-screen sticky top-0">
            <a href="{{ route('developers.dashboard') }}" class="flex items-center gap-2 px-6 h-16 border-b border-white/10 shrink-0">
                <span class="text-lg font-black tracking-tighter text-white uppercase italic">
                    GC<span class="text-[var(--brand-yellow)]">STATS</span>
                </span>
                <span class="text-[9px] font-black uppercase tracking-widest text-gray-500 border border-white/10 rounded-md px-1.5 py-0.5">{{ __('developers.dashboard.nav.title') }}</span>
            </a>

            <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-5">
                <div class="pb-5 mb-5 border-b border-white/10">
                    <a href="{{ route('developers.dashboard') }}"
                       @if(request()->routeIs('developers.dashboard')) aria-current="page" @endif
                       class="flex items-center gap-2.5 px-3 py-1.5 text-[12.5px] font-medium normal-case tracking-normal rounded-lg transition-all {{ request()->routeIs('developers.dashboard') ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        @svg('fas-gauge', 'w-3.5 h-3.5 shrink-0', ['aria-hidden' => 'true'])
                        <span class="truncate">{{ __('developers.dashboard.nav.dashboard') }}</span>
                    </a>
                </div>

                @include('developers.partials.nav', ['navGroups' => $navGroups])
            </nav>

            <div class="border-t border-white/10 p-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold uppercase tracking-widest rounded-lg text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                    @svg('fas-arrow-left', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                    {{ __('developers.dashboard.nav.back_to_site') }}
                </a>
            </div>
        </aside>

        <template x-teleport="body">
            <div x-show="sidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-[90] bg-black/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>
        </template>
        <aside x-show="sidebarOpen" x-cloak x-transition
               class="lg:hidden fixed inset-y-0 left-0 z-[95] w-64 bg-bg-main border-r border-white/10 flex flex-col">
            <div class="flex items-center justify-between px-6 h-16 border-b border-white/10">
                <span class="text-lg font-black tracking-tighter text-white uppercase italic">GC<span class="text-[var(--brand-yellow)]">STATS</span></span>
                <button @click="sidebarOpen = false" aria-label="{{ __('layout.nav.close_menu') }}">
                    @svg('fas-xmark', 'w-4 h-4 text-gray-400', ['aria-hidden' => 'true'])
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-5">
                <div class="pb-5 mb-5 border-b border-white/10">
                    <a href="{{ route('developers.dashboard') }}"
                       @if(request()->routeIs('developers.dashboard')) aria-current="page" @endif
                       class="flex items-center gap-2.5 px-3 py-1.5 text-[12.5px] font-medium normal-case tracking-normal rounded-lg transition-all {{ request()->routeIs('developers.dashboard') ? 'bg-[var(--brand-yellow)] text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        @svg('fas-gauge', 'w-3.5 h-3.5 shrink-0', ['aria-hidden' => 'true'])
                        <span class="truncate">{{ __('developers.nav.dashboard') }}</span>
                    </a>
                </div>

                @include('developers.partials.nav', ['navGroups' => $navGroups])
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between h-16 px-4 lg:px-8 border-b border-white/10 bg-black/20 backdrop-blur-xl shrink-0">
                <div class="flex items-center gap-4 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden" aria-label="{{ __('layout.nav.open_menu') }}">
                        @svg('fas-bars', 'w-4 h-4 text-gray-400', ['aria-hidden' => 'true'])
                    </button>
                    <h1 class="text-sm font-black uppercase tracking-widest text-white truncate">@yield('title', __('developers.dashboard.nav.title'))</h1>
                </div>

                <div class="flex items-center gap-4 shrink-0">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="{{ __('layout.account.logout') }}"
                                class="flex items-center justify-center w-9 h-9 rounded-lg bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-[var(--brand-yellow)]/50 transition-all">
                            @svg('fas-arrow-right-from-bracket', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8 min-w-0">
                @if (session('status'))
                    <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-lg px-4 py-3">
                        {{ __('developers.dashboard.status.'.session('status')) }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3">
                        {{ __('developers.dashboard.status.'.session('error')) }}
                    </div>
                @endif

                @if (isset($errors) && $errors->has('role'))
                    <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first('role') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
