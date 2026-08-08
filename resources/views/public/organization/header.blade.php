{{--
    GC-Stats — Organization header partial

    Renders the organization profile header (logo, name, types, country,
    liquipedia, socials) and the tab nav (overview / news, the latter
    pointing at the standalone news.organization page) — mirrors
    public.team.header, minus the change-request banner (no suggestion
    queue for organizations).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="mb-6">
    @if ($canManage ?? false)
        <div class="flex justify-end mb-3">
            <a href="{{ route('organization-dashboard.index', $organization) }}"
               class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gc-yellow hover:text-white transition">
                @svg('fas-gauge', 'w-2.5 h-2.5', ['aria-hidden' => 'true'])
                {{ __('organization.manage_link') }}
            </a>
        </div>
    @endif

    <div class="block group">
        <div class="relative bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 group-hover:bg-white/[0.04] group-hover:shadow-[0_20px_40px_rgba(0,0,0,0.6)]">
            <div class="p-4 md:p-6 flex flex-col gap-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 w-full">
                    <div class="flex items-center gap-4 md:gap-5 w-full">
                        <div class="relative flex-shrink-0">
                            @if ($organization->logo)
                                <img src="{{ $organization->logo }}" alt="{{ $organization->name }}"
                                     class="w-16 h-16 md:w-32 md:h-32 object-contain border border-white/10 rounded-lg bg-black/40 p-2">
                            @else
                                <div class="w-16 h-16 md:w-32 md:h-32 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10">
                                    <span class="text-2xl md:text-4xl font-black text-[var(--brand-yellow)]">
                                        {{ strtoupper(substr($organization->name, 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col justify-center min-w-0 flex-grow">
                            @if (! empty($organization->types()))
                                <p class="text-gray-300 text-sm font-medium mb-1 group-hover:text-gray-100 transition-colors">
                                    {{ implode(' · ', $organization->types()) }}
                                </p>
                            @endif

                            <div class="flex items-center gap-3">
                                @if ($organization->country_code)
                                    <span class="fi fi-{{ blank($organization->country_code) || $organization->country_code === 'inter' ? 'un' : strtolower($organization->country_code) }} shadow-sm flex-shrink-0"
                                          aria-label="{{ $organization->country_code }}" role="img"></span>
                                @endif

                                <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight leading-none group-hover:text-gc-yellow transition-colors">
                                    {{ $organization->name }}
                                </h1>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap md:flex-col gap-2 w-full md:w-auto md:min-w-[180px] md:pl-6">
                        @php
                            $socialConfig = collect([
                                'twitter'   => ['url' => 'https://x.com/', 'icon' => 'fab-x-twitter'],
                                'twitch'    => ['url' => 'https://twitch.tv/', 'icon' => 'fab-twitch'],
                                'tiktok'    => ['url' => 'https://tiktok.com/@', 'icon' => 'fab-tiktok'],
                                'instagram' => ['url' => 'https://instagram.com/', 'icon' => 'fab-instagram'],
                                'youtube'   => ['url' => 'https://youtube.com/@', 'icon' => 'fab-youtube'],
                                'discord'   => ['url' => '', 'icon' => 'fab-discord'],
                                'website'   => ['url' => '', 'icon' => 'fas-globe'],
                            ]);
                            $socials = $organization->socials ?? [];
                        @endphp

                        @if(!empty($socials) || $organization->liquipedia_link)
                            <div class="flex flex-col gap-1 min-w-[150px] justify-center border-t md:border-t-0 md:border-l border-border-subtle pt-4 md:pt-0 md:pl-6">
                                @if($organization->liquipedia_link)
                                    <a href="{{ $organization->liquipedia_link }}" target="_blank" rel="noopener noreferrer"
                                       aria-label="Liquipedia: {{ $organization->name }}"
                                       class="flex items-center gap-2 text-gray-400 hover:text-gc-yellow transition-colors group/socials py-0.5">
                                        <div class="w-6 h-6 flex items-center justify-center bg-bg-body rounded-sm group-hover/socials:bg-gc-yellow/10 flex-shrink-0">
                                            <img src="https://liquipedia.net/commons/extensions/TeamLiquidIntegration/resources/pagelogo/liquipedia_icon_menu.png"
                                                 alt="" class="w-[13px] h-[13px] object-contain">
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider truncate max-w-[100px]">Liquipedia</span>
                                    </a>
                                @endif

                                @foreach($socials as $platform => $username)
                                    @if($username && is_scalar($username) && $socialConfig->has($platform))
                                        @php $cfg = $socialConfig->get($platform); @endphp
                                        <a href="{{ $cfg['url'] . $username }}" target="_blank" rel="noopener noreferrer"
                                           aria-label="{{ ucfirst($platform) }}: {{ $username }}"
                                           class="flex items-center gap-2 text-gray-400 hover:text-gc-yellow transition-colors group/socials py-0.5">
                                            <div class="w-6 h-6 flex items-center justify-center bg-bg-body rounded-sm group-hover/socials:bg-gc-yellow/10 flex-shrink-0">
                                                @svg($cfg['icon'], 'w-[11px] h-[11px] inline-block text-[11px]', ['aria-hidden' => 'true'])
                                            </div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider truncate max-w-[100px]">
                                                @if($platform == "website" || $platform == "discord")
                                                    {{ ucfirst($platform) }}
                                                @else
                                                    {{ $username }}
                                                @endif
                                            </span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <nav aria-label="{{ __('organization.nav.aria_label') }}" class="bg-black/20 border-t border-white/5 overflow-x-auto no-scrollbar">
                <div class="flex justify-start md:justify-center min-w-max md:min-w-0">
                    @php
                        $navItems = [
                            ['route' => 'organizations.show', 'label' => __('organization.nav.overview')],
                            ['route' => 'organizations.experience', 'label' => __('organization.nav.experience')],
                            ['route' => 'news.organization', 'label' => __('organization.nav.news')],
                        ];
                    @endphp

                    @foreach($navItems as $item)
                        @php $isActive = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route'], [$organization->id, $organization->routeSlug()]) }}"
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
</div>
