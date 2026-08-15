{{--
    GC-Stats — Player header partial

    Renders the player profile header (photo, handle, name, country,
    current team, socials) shared across all player sub-pages.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="mb-6">
    @auth
        <div class="flex flex-col items-end gap-2 mb-3">
            @if (session('status') === 'change-request-submitted')
                <div class="w-full md:w-auto bg-green-500/10 border border-green-500/30 text-green-400 text-[11px] rounded-sm px-3 py-2">
                    {{ __('player.change_request.submitted_status') }}
                </div>
            @endif

            <a href="{{ route('players.change-requests.create', $player['id']) }}"
               class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gc-yellow hover:text-white transition">
                @svg('fas-pen', 'w-2.5 h-2.5', ['aria-hidden' => 'true'])
                {{ __('player.change_request.trigger') }}
            </a>
        </div>
    @endauth

    <div class="block group">
        <div class="relative bg-white/[0.02] border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 group-hover:bg-white/[0.04] group-hover:shadow-[0_20px_40px_rgba(0,0,0,0.6)]">
        <div class="p-4 md:p-6 flex flex-col gap-6">
            <x-player.identity-card :player="$player" />
        </div>

        <nav aria-label="{{ \App\Support\Pronouns::trans('player.nav.aria_label', $player['pronouns'] ?? \App\Support\Pronouns::FEMININE) }}" class="bg-black/20 border-t border-white/5 overflow-x-auto no-scrollbar">
            <div class="flex justify-start md:justify-center min-w-max md:min-w-0">
                @php
                    $navItems = [
                        ['route' => 'players.show', 'label' => __('player.nav.overview')],
                        ['route' => 'players.matches', 'label' => __('player.nav.matches')],
                        ['route' => 'players.stats', 'label' => __('player.nav.stats')],
                        ['route' => 'players.history', 'label' => __('player.nav.teams_history')],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php $isActive = request()->routeIs($item['route']); @endphp
                    <a href="{{ Route::has($item['route']) ? route($item['route'], [$player['id'], Str::routeSlug($player['handle'] ?? '', $player['id'])]) : '#' }}"
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
