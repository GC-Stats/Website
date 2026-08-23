{{--
    GC-Stats — Stats insights sidebar

    Small "leader" cards (top ACS/ADR/KAST/first kills/utility) summarizing
    the stats list already computed by the calling controller — no extra
    query, purely derived from $stats. Same pattern as maps-insights.blade.php.

    The "More plants" card is a silent easter egg: it looks like any other
    card (no hover/cursor cue) until clicked, at which point it swaps its
    content for a "Wingman" reveal AND toggles a bottom-right toast
    (public.partials.wingman-toast) with a fun message + credit — clicking
    again restores the card to its default plant-count view (and closes the
    toast, since both flip on the same event). The first time it's revealed,
    it also unlocks the Wingman navbar logo + accent theme (GCS.unlockWingman
    in resources/js/app.js — idempotent, so later toggles don't re-force the
    accent over a manual choice).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['insights', 'namespace'])

<div class="space-y-3" x-data="{ wingmanRevealed: false }">
    <h3 class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">
        {{ __($namespace.'.insights.title') }}
    </h3>

    <div class="grid grid-cols-2 gap-3">
        @foreach($insights as $insight)
            @php($isPlants = $insight['label'] === 'top_plants')

            <div @if($isPlants) @click="wingmanRevealed = !wingmanRevealed; window.dispatchEvent(new CustomEvent('wingman-toast-toggle')); if (wingmanRevealed) GCS.unlockWingman()" @endif
                 class="bg-white/[0.02] border border-white/5 rounded-2xl p-4">
                @if($isPlants)
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-2"
                       x-text="wingmanRevealed ? {{ Js::from(__('match.stats.wingman_best')) }} : {{ Js::from(__($namespace.'.insights.top_plants')) }}"></p>

                    <div class="flex items-center justify-between gap-3" x-show="!wingmanRevealed">
                        <span class="text-sm font-black text-white uppercase tracking-tight truncate">{{ $insight['name'] }}</span>
                        <span class="text-xs font-black text-[var(--brand-yellow)] shrink-0">{{ $insight['value'] }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-3" x-show="wingmanRevealed" x-cloak>
                        <span class="text-sm font-black text-white uppercase tracking-tight truncate">Wingman</span>
                        @include('public.partials.wingman-credit', ['imgClass' => 'h-12 w-12 shrink-0'])
                    </div>
                @else
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-2">
                        {{ __($namespace.'.insights.'.$insight['label']) }}
                    </p>

                    @if($insight['name'])
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-black text-white uppercase tracking-tight truncate">{{ $insight['name'] }}</span>
                            <span class="text-xs font-black text-[var(--brand-yellow)] shrink-0">{{ $insight['value'] }}</span>
                        </div>
                    @else
                        <span class="text-xs text-gray-600">—</span>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    @if($wingmanInsight = collect($insights)->firstWhere('label', 'top_plants'))
        @include('public.partials.wingman-toast', ['leaderName' => $wingmanInsight['name']])
    @endif
</div>
