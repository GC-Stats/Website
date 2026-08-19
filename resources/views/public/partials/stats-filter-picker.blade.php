{{--
    GC-Stats — Multi-select filter dropdown (agent/role/map)

    Same look/interaction as the stats table's column picker: a button
    showing the current selection count, a click-open panel of checkboxes.
    The panel is teleported to <body> and positioned with fixed coordinates
    computed from the trigger button — it must NOT stay nested under the
    filter bar's `backdrop-blur-md` container, because backdrop-filter
    creates a new CSS stacking context that traps any z-index inside it,
    letting later page content (the stats grid below) paint over the panel
    even at z-50. Teleporting sidesteps that entirely.

    Because the panel is no longer a DOM descendant of the page's filter
    <form> once teleported, its checkboxes are associated with that form via
    the HTML `form="{{ $formId }}"` attribute instead of nesting.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php($selected = $selected ?? [])

<div class="relative"
     x-data="{
        open: false,
        pos: { top: 0, left: 0 },
        toggle() {
            if (this.open) { this.close(true); return; }
            const r = this.$refs.trigger.getBoundingClientRect();
            this.pos = { top: r.bottom + 4, left: r.left };
            this.open = true;
        },
        close(submit) {
            if (! this.open) return;
            this.open = false;
            if (submit) document.getElementById('{{ $formId }}').requestSubmit();
        },
     }"
     @scroll.window="close(false)">
    <button type="button" x-ref="trigger" @click="toggle()"
            class="flex items-center gap-1.5 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-300 bg-black/40 hover:bg-black/60 rounded-lg border border-gray-800 transition-colors">
        {{ $label }}
        @if(count($selected))
            <span class="text-[var(--brand-yellow)]">({{ count($selected) }})</span>
        @endif
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition @click.outside="close(true)"
             :style="`top: ${pos.top}px; left: ${pos.left}px;`"
             class="fixed z-[9999] w-48 bg-[#111214] border border-white/10 rounded-lg shadow-2xl p-2 space-y-0.5">
            <div class="max-h-64 overflow-y-auto space-y-0.5">
                @forelse($options as $option)
                    <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white/[0.04] cursor-pointer text-[11px] text-gray-300">
                        <input type="checkbox" form="{{ $formId }}" name="{{ $name }}[]" value="{{ $option }}" @checked(in_array($option, $selected))
                               class="w-3.5 h-3.5 rounded border-gray-700 bg-black/40 text-[var(--brand-yellow)] focus:ring-0 focus:ring-offset-0">
                        {{ ucfirst($option) }}
                    </label>
                @empty
                    <p class="px-2 py-1.5 text-[11px] text-gray-600">{{ __('match.stats.filter_all') }}</p>
                @endforelse
            </div>

            @if(count($options))
                <button type="button" @click="close(true)"
                        class="w-full mt-1 pt-1.5 border-t border-white/10 text-[10px] font-black uppercase tracking-wider text-[var(--brand-yellow)] hover:text-white transition-colors">
                    {{ __('match.stats.apply_filter') }}
                </button>
            @endif
        </div>
    </template>
</div>
