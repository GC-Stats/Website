{{--
    GC-Stats — Organization dashboard shell

    Standalone layout for /organization/{organization}/dashboard: same
    fixed sidebar + top bar principle as admin.layout/developers.layout, but
    scoped to a single organization and reachable by anyone holding a role
    on it (see App\Services\OrganizationAccessService), not just site admins.

    Also doubles as the shell for dashboard/me (routes/personal-dashboard.php)
    — a lone author with no organization at all — by accepting
    $organization === null (or simply unset) as "personal mode". Every place
    that would otherwise read $organization->name/logo/id or hardcode an
    'organization-dashboard.*' route name goes through the $isPersonal/
    $routePrefix/$homeRouteParams/$dashboardTitle/$dashboardLogo variables
    computed below instead, so this one file serves both contexts without
    scattering an `$organization ? ... : ...` at every call site.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $organization = $organization ?? null;
    $isPersonal = $organization === null;
    $routePrefix = $isPersonal ? 'personal-dashboard.' : 'organization-dashboard.';
    $homeRouteParams = $isPersonal ? [] : [$organization];
    $dashboardTitle = $isPersonal
        ? (auth()->user()->newsAuthor?->name ?? auth()->user()->name)
        : $organization->name;
    $dashboardLogo = $isPersonal ? auth()->user()->newsAuthor?->logo : $organization->logo;
@endphp
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

    <title>@yield('title', '') | {{ $dashboardTitle }} | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg-main text-white" x-data="{ sidebarOpen: false }">

    <x-public.verify-email-banner />

    @php
        $canManageRoles = ! $isPersonal && (auth()->user()->can('organizations.roles.manage')
            || app(\App\Services\OrganizationAccessService::class)->hasOrganizationPermission(auth()->user(), $organization, null, 'organization.roles.manage'));

        $canViewNews = $isPersonal
            ? auth()->user()->can('news.author')
            : app(\App\Services\OrganizationAccessService::class)->hasOrganizationPermission(auth()->user(), $organization, 'news.view', 'organization.news.view');

        $canViewStreams = ! $isPersonal && app(\App\Services\OrganizationAccessService::class)
            ->hasOrganizationPermission(auth()->user(), $organization, 'streams.channels.view', 'organization.streams.view');

        $canLinkVods = ! $isPersonal && app(\App\Services\OrganizationAccessService::class)
            ->hasOrganizationPermission(auth()->user(), $organization, 'vods.matches.link', 'organization.vods.link');

        $canViewApiKeys = ! $isPersonal && app(\App\Services\OrganizationAccessService::class)
            ->hasOrganizationPermission(auth()->user(), $organization, 'api-keys.view', 'organization.api-keys.view');

        $canManageExperience = ! $isPersonal && app(\App\Services\OrganizationAccessService::class)->canManageExperience(auth()->user(), $organization);

        $navGroups = [
            [
                'label' => __('organization.dashboard.nav.group_news'),
                'items' => array_filter([
                    $canViewNews ? [
                        'route' => $routePrefix.'news.index',
                        'pattern' => [$routePrefix.'news.index', $routePrefix.'news.create', $routePrefix.'news.edit', $routePrefix.'news.update', $routePrefix.'news.destroy', $routePrefix.'news.publish', $routePrefix.'news.archive', $routePrefix.'news.validate', $routePrefix.'news.comments.*'],
                        'label' => __('organization.dashboard.nav.news'),
                        'icon' => 'fas-newspaper',
                    ] : null,
                    $canViewNews ? [
                        'route' => $routePrefix.'news.media.index',
                        'pattern' => $routePrefix.'news.media.*',
                        'label' => __('organization.dashboard.nav.news_media'),
                        'icon' => 'fas-images',
                    ] : null,
                    $canViewNews ? [
                        'route' => $routePrefix.'news.author.my',
                        'pattern' => $routePrefix.'news.author.*',
                        'label' => __('organization.dashboard.nav.news_author'),
                        'icon' => 'fas-pen',
                    ] : null,
                ]),
            ],
            [
                'label' => __('organization.dashboard.nav.group_production'),
                'items' => array_filter([
                    $canViewStreams ? [
                        'route' => 'organization-dashboard.streams.index',
                        'pattern' => 'organization-dashboard.streams.*',
                        'label' => __('organization.dashboard.nav.streams'),
                        'icon' => 'fas-tower-broadcast',
                    ] : null,
                    $canLinkVods ? [
                        'route' => 'organization-dashboard.vods.index',
                        'pattern' => 'organization-dashboard.vods.*',
                        'label' => __('organization.dashboard.nav.vods'),
                        'icon' => 'fas-clapperboard',
                    ] : null,
                    $canManageExperience ? [
                        'route' => 'organization-dashboard.experience.index',
                        'pattern' => 'organization-dashboard.experience.*',
                        'label' => __('organization.dashboard.nav.experience'),
                        'icon' => 'fas-medal',
                    ] : null,
                ]),
            ],
            [
                'label' => __('organization.dashboard.nav.group_administration'),
                'items' => array_filter([
                    $canManageRoles ? ['route' => 'organization-dashboard.roles.index', 'pattern' => 'organization-dashboard.roles.*', 'label' => __('organization.dashboard.nav.roles'), 'icon' => 'fas-user-shield'] : null,
                    $canViewApiKeys ? ['route' => 'organization-dashboard.api-keys.index', 'pattern' => 'organization-dashboard.api-keys.*', 'label' => __('organization.dashboard.nav.api_keys'), 'icon' => 'fas-key'] : null,
                ]),
            ],
        ];
    @endphp

    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-[var(--brand-yellow)] opacity-[0.03] blur-[120px]"></div>
        <div class="absolute top-[20%] -right-[5%] w-[30%] h-[30%] rounded-full bg-[var(--brand-yellow)] opacity-[0.02] blur-[100px]"></div>
    </div>

    <div class="flex min-h-screen">
        <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 border-r border-white/10 bg-black/40 backdrop-blur-xl h-screen sticky top-0">
            <a href="{{ route($routePrefix.'index', $homeRouteParams) }}" class="flex items-center gap-2 px-6 h-16 border-b border-white/10 shrink-0 min-w-0">
                @if ($dashboardLogo)
                    <img src="{{ $dashboardLogo }}" alt="" class="w-6 h-6 object-contain shrink-0">
                @else
                    @svg('fas-pen', 'w-4 h-4 text-[var(--brand-yellow)] shrink-0', ['aria-hidden' => 'true'])
                @endif
                <span class="text-sm font-black tracking-tight text-white uppercase truncate">{{ $dashboardTitle }}</span>
            </a>

            <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-5">
                <div class="pb-5 mb-5 border-b border-white/10">
                    <a href="{{ route($routePrefix.'index', $homeRouteParams) }}"
                       @if(request()->routeIs($routePrefix.'index')) aria-current="page" @endif
                       class="flex items-center gap-2.5 px-3 py-1.5 text-[12.5px] font-medium normal-case tracking-normal rounded-lg transition-all {{ request()->routeIs($routePrefix.'index') ? 'bg-gc-yellow text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        @svg('fas-building', 'w-3.5 h-3.5 shrink-0', ['aria-hidden' => 'true'])
                        <span class="truncate">{{ __('organization.dashboard.nav.overview') }}</span>
                    </a>
                </div>

                @include('organization.partials.nav', ['navGroups' => $navGroups, 'routeParams' => $homeRouteParams])
            </nav>

            <div class="border-t border-white/10 p-3 space-y-1">
                @php
                    $publicPageRoute = $isPersonal
                        ? (auth()->user()->newsAuthor ? route('news.author', auth()->user()->newsAuthor->slug) : null)
                        : route('organizations.show', [$organization->id, $organization->routeSlug()]);
                @endphp
                @if ($publicPageRoute)
                    <a href="{{ $publicPageRoute }}" target="_blank" rel="noopener"
                       class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold uppercase tracking-widest rounded-lg text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                        @svg('fas-arrow-up-right-from-square', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                        {{ __('organization.dashboard.nav.public_page') }}
                    </a>
                @endif
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-bold uppercase tracking-widest rounded-lg text-gray-400 hover:bg-white/5 hover:text-white transition-all">
                    @svg('fas-arrow-left', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                    {{ __('organization.dashboard.nav.back_to_site') }}
                </a>
            </div>
        </aside>

        <template x-teleport="body">
            <div x-show="sidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-[90] bg-black/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>
        </template>
        <aside x-show="sidebarOpen" x-cloak x-transition
               class="lg:hidden fixed inset-y-0 left-0 z-[95] w-64 bg-bg-main border-r border-white/10 flex flex-col">
            <div class="flex items-center justify-between px-6 h-16 border-b border-white/10">
                <span class="text-sm font-black tracking-tight text-white uppercase truncate">{{ $dashboardTitle }}</span>
                <button @click="sidebarOpen = false" aria-label="{{ __('layout.nav.close_menu') }}">
                    @svg('fas-xmark', 'w-4 h-4 text-gray-400', ['aria-hidden' => 'true'])
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-5">
                <div class="pb-5 mb-5 border-b border-white/10">
                    <a href="{{ route($routePrefix.'index', $homeRouteParams) }}"
                       @if(request()->routeIs($routePrefix.'index')) aria-current="page" @endif
                       class="flex items-center gap-2.5 px-3 py-1.5 text-[12.5px] font-medium normal-case tracking-normal rounded-lg transition-all {{ request()->routeIs($routePrefix.'index') ? 'bg-gc-yellow text-black' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        @svg('fas-building', 'w-3.5 h-3.5 shrink-0', ['aria-hidden' => 'true'])
                        <span class="truncate">{{ __('organization.dashboard.nav.overview') }}</span>
                    </a>
                </div>

                @include('organization.partials.nav', ['navGroups' => $navGroups, 'routeParams' => $homeRouteParams])
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between h-16 px-4 lg:px-8 border-b border-white/10 bg-black/20 backdrop-blur-xl shrink-0">
                <div class="flex items-center gap-4 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden" aria-label="{{ __('layout.nav.open_menu') }}">
                        @svg('fas-bars', 'w-4 h-4 text-gray-400', ['aria-hidden' => 'true'])
                    </button>
                    <h1 class="text-sm font-black uppercase tracking-widest text-white truncate">@yield('title', $dashboardTitle)</h1>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8 min-w-0">
                @if (session('status'))
                    <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-lg px-4 py-3">
                        {{ __('admin.status.'.session('status')) }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3">
                        {{ __('admin.status.'.session('error')) }}
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
