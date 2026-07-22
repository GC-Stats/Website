{{--
    GC-Stats — Searchable single-phase picker

    Alpine-driven search box backed by a JSON search endpoint, used to pick a
    qualification rule's destination phase across any tournament (a phase
    can qualify into a phase of a different tournament, e.g. regional ->
    major). Posts a single hidden `name` input, unlike the tag-style
    <x-relation-picker> this is modeled after.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['name', 'label', 'searchUrl', 'selected' => null])

<div x-data="{
        query: '',
        results: [],
        selected: {{ $selected ? json_encode(['id' => $selected['id'], 'label' => $selected['label']]) : 'null' }},
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const response = await fetch(`{{ $searchUrl }}?q=${encodeURIComponent(this.query)}`);
            this.results = await response.json();
        },
        pick(item) {
            this.selected = item;
            this.results = [];
            this.query = '';
        },
        clear() {
            this.selected = null;
        },
     }">
    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ $label }}</label>

    <input type="hidden" name="{{ $name }}" :value="selected ? selected.id : ''">

    <div x-show="selected" x-cloak class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2 mb-2">
        <span class="text-xs text-white font-semibold" x-text="selected ? selected.label : ''"></span>
        <button type="button" @click="clear()" class="text-gray-500 hover:text-red-400 transition">&times;</button>
    </div>

    <div x-show="!selected" class="relative">
        <input type="text" x-model="query" @input.debounce.300ms="search()"
               placeholder="{{ __('admin.tournaments.qualifications.phase_search_placeholder') }}"
               class="w-full bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">

        <div x-show="results.length" x-cloak @click.outside="results = []"
             class="absolute z-10 mt-1 w-full bg-bg-card border border-white/10 rounded-lg shadow-xl max-h-48 overflow-y-auto">
            <template x-for="item in results" :key="item.id">
                <button type="button" @click="pick(item)" x-text="item.label"
                        class="block w-full text-left px-3 py-2 text-xs text-white hover:bg-white/5 transition"></button>
            </template>
        </div>
    </div>
</div>
