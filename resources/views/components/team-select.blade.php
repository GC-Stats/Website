{{--
    GC-Stats — Searchable team select

    Reads as a plain <select> (same field styling as every other dropdown
    in the form, chevron included) until clicked — only then does a small
    search field appear inside the opened panel, above the team list.
    Client-side filter over a small, already-loaded team list (a
    tournament's roster).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['name', 'label' => null, 'teams', 'selected' => null, 'placeholder' => null])

<div
    {{ $attributes }}
    x-data="{
        teams: {{ \Illuminate\Support\Js::from($teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()) }},
        query: '',
        open: false,
        value: {{ \Illuminate\Support\Js::from($selected) }},
        placeholder: {{ \Illuminate\Support\Js::from($placeholder ?? __('admin.matches.unknown_team')) }},

        get filtered() {
            if (this.query.length < 1) return this.teams;
            const q = this.query.toLowerCase();
            return this.teams.filter(t => t.name.toLowerCase().includes(q));
        },

        get selectedName() {
            const found = this.teams.find(t => String(t.id) === String(this.value));
            return found ? found.name : this.placeholder;
        },

        toggle() {
            this.open = ! this.open;
            if (this.open) {
                this.query = '';
                this.$nextTick(() => this.$refs.search.focus());
            }
        },

        select(id) {
            this.value = id;
            this.open = false;
        },
    }"
    @click.outside="open = false"
>
    @if ($label)
        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ $label }}</span>
    @endif

    <div class="relative">
        <button type="button" @click="toggle()"
                class="w-full h-[42px] flex items-center justify-between bg-white/5 border border-white/10 rounded-lg px-4 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <span x-text="selectedName" class="truncate"></span>
            <svg class="w-3 h-3 text-gray-500 shrink-0 ml-2" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="open" x-cloak
             class="absolute z-10 mt-1 w-full bg-bg-card border border-white/10 rounded-lg shadow-xl overflow-hidden">
            <input type="text" x-ref="search" x-model="query" placeholder="{{ __('admin.matches.team_search') }}"
                   class="w-full bg-white/5 border-b border-white/10 px-4 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <div class="max-h-48 overflow-y-auto">
                <button type="button" @click="select('')"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-500 hover:bg-white/5 transition">
                    {{ $placeholder ?? __('admin.matches.unknown_team') }}
                </button>
                <template x-for="team in filtered" :key="team.id">
                    <button type="button" @click="select(team.id)" x-text="team.name"
                            class="block w-full text-left px-4 py-2 text-sm text-white hover:bg-white/5 transition"></button>
                </template>
                <p x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-500">—</p>
            </div>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" x-model="value">
</div>
