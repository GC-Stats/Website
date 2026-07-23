{{--
    GC-Stats — User profile header partial

    Mirrors player/header.blade.php's format (size, structure, nav bar).
    The "socials" column is repurposed here to link out to this user's
    player profile and publishers instead of social media accounts, since a
    User doesn't have those. The "News" nav tab (only shown when the user
    has a linked News\Author profile) replaces what used to be a separate
    news.author page — see UserProfileController::news(). Expects
    $profileUser and $publishers (see UserProfileController::sharedData()).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="block group mb-6">
    <div class="relative bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 group-hover:bg-white/[0.04] group-hover:shadow-[0_20px_40px_rgba(0,0,0,0.6)]">
        <div class="p-4 md:p-6 flex flex-col gap-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 w-full">
                <div class="flex items-center gap-4 md:gap-5 w-full">
                    <x-user-avatar :user="$profileUser" class="w-16 h-16 md:w-32 md:h-32 rounded-lg bg-black/40 border border-white/10 text-2xl md:text-5xl flex-shrink-0" />

                    <div class="flex flex-col justify-center min-w-0 flex-grow">
                        <p class="text-gray-300 text-sm font-medium mb-1 group-hover:text-gray-100 transition-colors">
                            {{ '@'.$profileUser->username }}
                        </p>

                        <h1 class="text-2xl md:text-3xl font-black text-white uppercase tracking-tight leading-none group-hover:text-gc-yellow transition-colors truncate">
                            {{ $profileUser->name }}
                        </h1>

                        @if ($profileUser->team)
                            <a href="{{ route('teams.show', [$profileUser->team->id, $profileUser->team->routeSlug()]) }}"
                               class="inline-flex items-center gap-2 self-start bg-white/5 border border-white/10 rounded-sm px-3 py-1.5 mt-2 hover:border-gc-yellow/50 transition">
                                <img src="{{ $profileUser->team->logo }}" alt="{{ $profileUser->team->name }}" class="w-4 h-4 object-contain logo-filter">
                                @if ($profileUser->team_tag)
                                    <span class="text-[11px] font-black uppercase tracking-widest text-gc-yellow">
                                        {{ $profileUser->team_tag }}
                                    </span>
                                @else
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-300">
                                        {{ __('user.profile.fan_of') }} {{ $profileUser->team->name }}
                                    </span>
                                @endif
                            </a>
                        @endif
                    </div>
                </div>

                @if ($profileUser->player || $publishers->isNotEmpty())
                    <div class="flex flex-col gap-1 min-w-[180px] justify-center w-full md:w-auto border-t md:border-t-0 md:border-l border-border-subtle pt-4 md:pt-0 md:pl-6 mr-4/">
                        @if ($profileUser->player)
                            <a href="{{ route('players.show', [$profileUser->player->id, Str::routeSlug($profileUser->player->handle, $profileUser->player->id)]) }}"
                               class="flex items-center gap-2 text-gray-400 hover:text-gc-yellow transition-colors group/socials py-0.5">
                                <div class="w-6 h-6 flex items-center justify-center bg-bg-body rounded-sm group-hover/socials:bg-gc-yellow/10 flex-shrink-0">
                                    @svg('fas-gamepad', 'w-[11px] h-[11px] inline-block text-[11px]', ['aria-hidden' => 'true'])
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider whitespace-nowrap">
                                    {{ __('user.profile.view_player_profile') }}
                                </span>
                            </a>
                        @endif

                        @foreach ($publishers as $publisher)
                            <a href="{{ route('news.publisher', $publisher->slug) }}"
                               class="flex items-center gap-2 text-gray-400 hover:text-gc-yellow transition-colors group/socials py-0.5">
                                <div class="w-6 h-6 flex items-center justify-center bg-bg-body rounded-sm group-hover/socials:bg-gc-yellow/10 flex-shrink-0">
                                    @if ($publisher->logo)
                                        <img src="{{ $publisher->logo }}" alt="{{ $publisher->name }}" class="w-full h-full object-contain">
                                    @else
                                        @svg('fas-newspaper', 'w-[11px] h-[11px] inline-block text-[11px]', ['aria-hidden' => 'true'])
                                    @endif
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider truncate max-w-[140px]">
                                    {{ $publisher->name }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <nav aria-label="{{ __('user.nav.aria_label') }}" class="bg-black/20 border-t border-white/5 overflow-x-auto no-scrollbar">
            <div class="flex justify-start md:justify-center min-w-max md:min-w-0">
                @php
                    $navItems = [
                        ['route' => 'users.show', 'label' => __('user.nav.overview')],
                    ];

                    if ($profileUser->newsAuthor) {
                        $navItems[] = ['route' => 'users.news', 'label' => __('user.nav.news')];
                    }
                @endphp

                @foreach ($navItems as $item)
                    @php $isActive = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route'], $profileUser->username) }}"
                       @if($isActive) aria-current="page" @endif
                       class="relative px-6 md:px-10 py-4 text-[10px] md:text-[11px] font-black uppercase tracking-[0.2em] transition-all group/navbar whitespace-nowrap {{ $isActive ? 'text-[var(--brand-yellow)]' : 'text-gray-500 hover:text-white' }}">
                        {{ $item['label'] }}

                        <span class="absolute bottom-0 left-0 h-0.5 bg-[var(--brand-yellow)] transition-all duration-300 ease-in-out
                            {{ $isActive ? 'w-full' : 'w-0 group-hover/navbar:w-full' }}">
                        </span>
                    </a>
                @endforeach
            </div>
        </nav>
    </div>
</div>
