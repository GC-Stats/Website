{{--
    GC-Stats — Custom-styled select replacement

    Chrome (and Chromium forks like Brave) render a native <select>'s open
    dropdown list via an out-of-process popup that, in practice, ignores the
    page's `color-scheme` CSS and the `<meta name="color-scheme">` tag —
    the popup stays light even with a fully dark OS/browser/page (verified:
    computed `color-scheme` on the element is `dark`, yet the list renders
    white). There's no CSS-only fix, so this component reproduces a native
    select's look with an Alpine-driven listbox we fully control the
    styling of, same approach already used by team-select.blade.php and
    timezone-select.blade.php.

    Two usage modes:

    1. Plain form field — submits as `name` like a native select:
        <x-styled-select name="status" :options="['' => 'Tous les statuts', 'live' => 'En cours']" :selected="$status" />

       `autosubmit` mirrors the native `onchange="this.form.submit()"`
       selects used on filter bars (submits the enclosing form as soon as a
       value is picked).

    2. Bound to reactive Alpine state (dynamic rows, x-for tables, ...) —
       no `name`/form submission, just a two-way `x-model` like any native
       input, via Alpine's `x-modelable`:
        <x-styled-select x-model="stat.player_id" :options="$playerOptions" />

    3. Navigation switcher — option values are full URLs, mirrors the
       native `onchange="window.location = this.value"` key switchers:
        <x-styled-select navigate :selected="$currentUrl" :options="$urlsByLabel" />

    4. Livewire-bound — same idea as `x-model`, but for a Livewire property:
        <x-styled-select wire:model="reportEmoteId" :options="$emoteOptions" />

    A dynamic per-row `name` (x-for tables building `foo[index][field]`
    field names) or a reactive `disabled` condition can't go through Blade's
    `:name`/`:disabled` — those are intercepted server-side as prop
    bindings, and the expression (a JS template literal, a reference to an
    Alpine-scope function...) isn't valid PHP. Use the unambiguous
    `x-bind:` form instead, which passes straight through untouched:
        <x-styled-select x-model="row.team" x-bind:name="`maps[${index}][team]`" x-bind:disabled="!canPickSide(row)" :options="..." />

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['name' => null, 'options' => [], 'selected' => null, 'autosubmit' => false, 'navigate' => false, 'disabled' => false])

@php
    $optionList = collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values();
@endphp

<div
    x-data="{
        open: false,
        value: {{ \Illuminate\Support\Js::from((string) $selected) }},
        options: {{ \Illuminate\Support\Js::from($optionList) }},
        rect: { top: 0, left: 0, width: 0 },
        get selectedLabel() {
            const found = this.options.find(o => o.value === String(this.value));
            return found ? found.label : this.value;
        },


        toggle(event) {
            this.open = ! this.open;
            if (this.open) {
                const r = event.currentTarget.getBoundingClientRect();
                this.rect = { top: r.bottom + 4, left: r.left, width: r.width };
            }
        },
        select(v) {
            this.value = v;
            this.open = false;
            this.$nextTick(() => {
                this.$refs.root.dispatchEvent(new CustomEvent('change', { bubbles: true }));
                @if($navigate)
                    window.location = v;
                @elseif($autosubmit)
                    this.$refs.hidden?.closest('form')?.submit();
                @endif
            });
        },
    }"
    x-modelable="value"
    x-ref="root"
    {{ $attributes->whereDoesntStartWith(['wire:', 'x-bind:name', 'x-bind:disabled'])->merge(['class' => 'relative']) }}
>
    {{-- .stop keeps this click from reaching the teleported dropdown's own
         @click.outside (they're separate DOM subtrees once teleported), which
         would otherwise immediately re-close a dropdown this same click just opened. --}}
    <button type="button" @click.stop="toggle($event)" @if($disabled) disabled @endif
            {{ $attributes->whereStartsWith('x-bind:disabled') }}
            class="h-[42px] w-full flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition disabled:opacity-40 disabled:pointer-events-none">
        <span x-text="selectedLabel" class="truncate"></span>
        <svg class="w-3 h-3 text-gray-500 shrink-0" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak @click.outside="open = false" @scroll.window="open = false"
             data-dropdown-portal
             :style="`top: ${rect.top}px; left: ${rect.left}px; width: ${rect.width}px;`"
             class="fixed z-[100] min-w-max bg-bg-card border border-white/10 rounded-lg shadow-xl max-h-64 overflow-y-auto">
            <template x-for="opt in options" :key="opt.value">
                <button type="button" @click="select(opt.value)" x-text="opt.label"
                        class="block w-full text-left px-4 py-2 text-sm text-white hover:bg-white/5 transition"
                        :class="opt.value === String(value) && 'bg-white/5'"></button>
            </template>
        </div>
    </template>

    @if($name || $attributes->hasAny(['x-bind:name']) || collect($attributes->getAttributes())->keys()->contains(fn ($k) => str_starts_with($k, 'wire:')))
        <input type="hidden" name="{{ $name }}" x-model="value" x-ref="hidden" @if($disabled) disabled @endif
               {{ $attributes->whereStartsWith(['wire:', 'x-bind:name', 'x-bind:disabled']) }}>
    @endif
</div>
