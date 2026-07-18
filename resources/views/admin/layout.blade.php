{{--
    GC-Stats — Admin dashboard shell

    Standalone layout for /admin: fixed sidebar + top bar, no public-site
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

    <title>@yield('title', '') | {{ __('admin.nav.title') }} | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg-main text-white" x-data="{ sidebarOpen: false }">

    @php
        $navGroups = [
            [
                'label' => __('admin.nav.group_moderation'),
                'items' => [
                    ['route' => 'admin.reports.index', 'pattern' => 'admin.reports.*', 'label' => __('admin.nav.reports'), 'icon' => 'fas-flag', 'can' => 'reports.view'],
                    ['route' => 'admin.sanctions.index', 'pattern' => 'admin.sanctions.*', 'label' => __('admin.nav.sanctions'), 'icon' => 'fas-gavel', 'can' => 'sanctions.view'],
                    ['route' => 'admin.activity.index', 'pattern' => 'admin.activity.*', 'label' => __('admin.nav.activity'), 'icon' => 'fas-clock-rotate-left', 'can' => 'activity.view'],
                ],
            ],
            [
                'label' => __('admin.nav.group_access'),
                'items' => [
                    ['route' => 'admin.roles.index', 'pattern' => 'admin.roles.*', 'label' => __('admin.nav.roles'), 'icon' => 'fas-user-shield', 'can' => 'manage-roles'],
                ],
            ],
        ];
    @endphp

    <div class="flex min-h-screen">
        <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 border-r border-white/10 bg-black/40">
            <a href="{{ route('home') }}" class="flex items-center gap-2 px-6 h-16 border-b border-white/10 shrink-0">
                <span class="text-lg font-black tracking-tighter text-white uppercase italic">
                    GC<span class="text-[var(--brand-yellow)]">STATS</span>
                </span>
                <span class="text-[9px] font-black uppercase tracking-widest text-gray-500 border border-white/10 rounded px-1.5 py-0.5">{{ __('admin.nav.title') }}</span>
            </a>

            <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-8">
                @foreach ($navGroups as $group)
                    @php $visibleItems = collect($group['items'])->filter(fn ($item) => auth()->user()->can($item['can'])); @endphp
                    @if ($visibleItems->isNotEmpty())
                        <div>
                            <p class="px-3 mb-2 text-[9px] font-black uppercase tracking-[0.2em] text-gray-600">{{ $group['label'] }}</p>
                            <div class="space-y-1">
                                @foreach ($visibleItems as $item)
                                    <a href="{{ route($item['route']) }}"
                                       @if(request()->routeIs($item['pattern'])) aria-current="page" @endif
                                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold uppercase tracking-widest rounded-lg transition-all {{ request()->routeIs($item['pattern']) ? 'bg-gc-yellow text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                                        @svg($item['icon'], 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-white/10 p-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold uppercase tracking-widest rounded-lg text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                    @svg('fas-arrow-left', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                    {{ __('admin.nav.back_to_site') }}
                </a>
            </div>
        </aside>

        <template x-teleport="body">
            <div x-show="sidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-[90] bg-black/60" @click="sidebarOpen = false"></div>
        </template>
        <aside x-show="sidebarOpen" x-cloak x-transition
               class="lg:hidden fixed inset-y-0 left-0 z-[95] w-64 bg-bg-main border-r border-white/10 flex flex-col">
            <div class="flex items-center justify-between px-6 h-16 border-b border-white/10">
                <span class="text-lg font-black tracking-tighter text-white uppercase italic">GC<span class="text-[var(--brand-yellow)]">STATS</span></span>
                <button @click="sidebarOpen = false" aria-label="{{ __('layout.nav.close_menu') }}">
                    @svg('fas-xmark', 'w-4 h-4 text-gray-400', ['aria-hidden' => 'true'])
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-8">
                @foreach ($navGroups as $group)
                    @php $visibleItems = collect($group['items'])->filter(fn ($item) => auth()->user()->can($item['can'])); @endphp
                    @if ($visibleItems->isNotEmpty())
                        <div>
                            <p class="px-3 mb-2 text-[9px] font-black uppercase tracking-[0.2em] text-gray-600">{{ $group['label'] }}</p>
                            <div class="space-y-1">
                                @foreach ($visibleItems as $item)
                                    <a href="{{ route($item['route']) }}"
                                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold uppercase tracking-widest rounded-lg transition-all {{ request()->routeIs($item['pattern']) ? 'bg-gc-yellow text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                                        @svg($item['icon'], 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between h-16 px-4 lg:px-8 border-b border-white/10 shrink-0">
                <div class="flex items-center gap-4 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden" aria-label="{{ __('layout.nav.open_menu') }}">
                        @svg('fas-bars', 'w-4 h-4 text-gray-400', ['aria-hidden' => 'true'])
                    </button>
                    <h1 class="text-sm font-black uppercase tracking-widest text-white truncate">@yield('title', __('admin.nav.title'))</h1>
                </div>

                <div class="flex items-center gap-4 shrink-0">
                    <span class="hidden sm:block text-xs text-gray-400">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="{{ __('layout.account.logout') }}"
                                class="flex items-center justify-center w-9 h-9 rounded-lg bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition-all">
                            @svg('fas-arrow-right-from-bracket', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8 min-w-0">
                @if (session('status'))
                    <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                        {{ __('admin.status.'.session('status')) }}
                    </div>
                @endif

                @error('role')
                    <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3">{{ $message }}</div>
                @enderror

                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
