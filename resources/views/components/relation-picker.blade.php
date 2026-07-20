{{--
    GC-Stats — Searchable multi-select picker

    Alpine-driven tag picker backed by a JSON search endpoint (no full page
    reload, so it's safe to use on an unsaved create form). Selected items
    post as hidden `name[]` inputs.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['name', 'label', 'searchUrl', 'type', 'selected' => []])

<div x-data="{
        query: '',
        results: [],
        selected: {{ json_encode(collect($selected)->map(fn ($item) => ['id' => $item->id, 'label' => $item->label])->values()) }},
        loading: false,
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            this.loading = true;
            const response = await fetch(`{{ $searchUrl }}?type={{ $type }}&q=${encodeURIComponent(this.query)}`);
            const data = await response.json();
            this.loading = false;
            this.results = data.filter(item => !this.selected.some(s => s.id === item.id));
        },
        add(item) {
            this.selected.push(item);
            this.results = [];
            this.query = '';
        },
        remove(id) {
            this.selected = this.selected.filter(s => s.id !== id);
        },
     }">
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ $label }}</label>

    <div class="flex flex-wrap gap-2 mb-2" x-show="selected.length">
        <template x-for="item in selected" :key="item.id">
            <span class="inline-flex items-center gap-1.5 bg-[#050505] border border-border-subtle rounded-sm px-2.5 py-1 text-xs text-white">
                <input type="hidden" :name="`{{ $name }}[]`" :value="item.id">
                <span x-text="item.label"></span>
                <button type="button" @click="remove(item.id)" class="text-gray-500 hover:text-red-400 transition">&times;</button>
            </span>
        </template>
    </div>

    <div class="relative">
        <input type="text" x-model="query" @input.debounce.300ms="search()"
               placeholder="{{ __('admin.news.form.picker_placeholder') }}"
               class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

        <div x-show="results.length" x-cloak @click.outside="results = []"
             class="absolute z-10 mt-1 w-full bg-bg-card border border-border-subtle rounded-sm shadow-xl max-h-48 overflow-y-auto">
            <template x-for="item in results" :key="item.id">
                <button type="button" @click="add(item)" x-text="item.label"
                        class="block w-full text-left px-4 py-2 text-sm text-white hover:bg-white/5 transition"></button>
            </template>
        </div>
    </div>
</div>
