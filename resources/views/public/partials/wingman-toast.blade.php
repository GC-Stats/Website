{{--
    GC-Stats — Wingman easter egg toast

    Bottom-right notification toggled alongside the "More plants" insight
    card's own reveal (stats-insights.blade.php flips wingmanRevealed and
    dispatches wingman-toast-toggle on the same click). Wingman "talks" in
    first person, taunting whoever actually leads the plant count, and
    auto-dismisses itself after a few seconds. Ends with the Wingman credit
    trigger on its own line (opens the artist modal, see
    wingman-credit.blade.php / wingman-modal.blade.php).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['leaderName' => null])

<div x-data="{ wingmanToastOpen: false, wingmanToastTimer: null }"
     @wingman-toast-toggle.window="
        wingmanToastOpen = !wingmanToastOpen;
        clearTimeout(wingmanToastTimer);
        if (wingmanToastOpen) { wingmanToastTimer = setTimeout(() => wingmanToastOpen = false, 6000); }
     ">
    <template x-teleport="body">
        <div x-show="wingmanToastOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-3"
             x-cloak
             role="status"
             class="fixed bottom-6 right-6 z-[95] w-72 bg-bg-card border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.7)] p-4">

            <button type="button" @click="wingmanToastOpen = false; clearTimeout(wingmanToastTimer)"
                    aria-label="{{ __('layout.wingman.modal_close') }}"
                    class="absolute top-2.5 right-2.5 text-gray-500 hover:text-white transition-colors">
                <x-fas-xmark class="w-3.5 h-3.5" aria-hidden="true" />
            </button>

            <p class="text-xs text-gray-300 leading-relaxed pr-4">
                {{ __('match.stats.wingman_message', ['name' => $leaderName ?? '—']) }}
            </p>

            <div class="flex items-center justify-between gap-3 mt-3 pt-3 border-t border-white/5">
                <span class="text-sm font-black text-white uppercase tracking-tight truncate">Wingman</span>
                @include('public.partials.wingman-credit', ['imgClass' => 'h-6 w-6 shrink-0'])
            </div>
        </div>
    </template>
</div>
