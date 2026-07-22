{{--
    GC-Stats — Admin: tournament create/edit form (shared partial)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $currentCategory = old('category', $tournament->category ?? '');
    $isCustomCategory = $currentCategory !== '' && ! in_array($currentCategory, $categories, true);

    $flatPhases = collect();
    if ($tournament ?? null) {
        $walk = function ($phases, $parentTempId = null) use (&$walk, &$flatPhases) {
            foreach ($phases as $phase) {
                $tempId = 'phase_'.$phase->id;
                $flatPhases->push([
                    'id' => $phase->id,
                    'temp_id' => $tempId,
                    'parent_id' => $parentTempId,
                    'name' => $phase->name,
                    'format' => $phase->format,
                    'order' => $phase->order,
                ]);
                $walk($phase->children, $tempId);
            }
        };
        $walk($tournament->rootPhases);
    }
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.tournaments.information') }}</h2>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.name') }}</span>
            <input type="text" name="name" value="{{ old('name', $tournament->name ?? '') }}" required
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </label>

        <div class="grid grid-cols-2 gap-4">
            <label class="block">
                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.region') }}</span>
                <select name="region" required
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                    @foreach ($regions as $r)
                        <option value="{{ $r }}" @selected(old('region', $tournament->region ?? '') === $r)>{{ $r }}</option>
                    @endforeach
                </select>
            </label>

            <div x-data="{ custom: {{ $isCustomCategory ? 'true' : 'false' }} }" class="block">
                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.category') }}</span>
                {{-- The select is the only element named "category" — it stays in the DOM (holding
                     "__custom__") while hidden, so a second hidden input with the same name would
                     always be posted too and silently clobber whichever value came first. --}}
                <select name="category" x-ref="categorySelect" x-show="!custom" @change="custom = ($event.target.value === '__custom__')"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                    @foreach ($categories as $c)
                        <option value="{{ $c }}" @selected($currentCategory === $c)>{{ $c }}</option>
                    @endforeach
                    <option value="__custom__" @selected($isCustomCategory)>{{ __('admin.tournaments.category_custom') }}</option>
                </select>
                <div x-show="custom" class="flex gap-2">
                    <input type="text" name="category_custom" value="{{ $isCustomCategory ? $currentCategory : '' }}" placeholder="{{ __('admin.tournaments.category_custom') }}"
                           class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    <button type="button" @click="custom = false; $refs.categorySelect.value = '{{ $categories[0] }}'"
                            class="font-bold uppercase text-[10px] tracking-widest px-3 rounded-lg bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        &times;
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <label class="block">
                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.start_date') }}</span>
                <input type="date" name="start_date" value="{{ old('start_date', optional($tournament->start_date ?? null)->format('Y-m-d')) }}" required
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            </label>

            <label class="block">
                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.end_date') }}</span>
                <input type="date" name="end_date" value="{{ old('end_date', optional($tournament->end_date ?? null)->format('Y-m-d')) }}" required
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            </label>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <label class="block">
                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.location') }}</span>
                <input type="text" name="location" value="{{ old('location', $tournament->location ?? '') }}"
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            </label>

            <label class="block">
                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.prize_pool') }}</span>
                <input type="text" name="prize_pool" value="{{ old('prize_pool', $tournament->prize_pool ?? '') }}"
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            </label>
        </div>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.liquipedia_link') }}</span>
            <input type="url" name="liquipedia_link" value="{{ old('liquipedia_link', $tournament->liquipedia_link ?? '') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.description') }}</span>
            <textarea name="description" rows="3"
                      class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('description', $tournament->description ?? '') }}</textarea>
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.point_type') }}</span>
            <select name="point_type_id"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.tournaments.point_type_none') }}</option>
                @foreach ($pointTypes as $pointType)
                    <option value="{{ $pointType->id }}" @selected((int) old('point_type_id', $tournament->point_type_id ?? '') === $pointType->id)>{{ $pointType->name }}</option>
                @endforeach
            </select>
        </label>

        @if ($tournament ?? null)
            <label class="block">
                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.status_column') }}</span>
                <select name="status"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                    @foreach (['upcoming', 'live', 'finished'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $tournament->status) === $s)>{{ __('admin.tournaments.status.'.$s) }}</option>
                    @endforeach
                </select>
            </label>
        @endif
    </div>

    <div x-data="{
            phases: {{ Illuminate\Support\Js::from($flatPhases->values()) }},
            byTempId(tempId) { return this.phases.find(p => p.temp_id === tempId) ?? null; },
            depth(phase) {
                let d = 0, current = phase; const seen = new Set();
                while (current?.parent_id && !seen.has(current.temp_id)) {
                    seen.add(current.temp_id); current = this.byTempId(current.parent_id); d++;
                }
                return d;
            },
            hasChildren(phase) { return this.phases.some(p => p.parent_id === phase.temp_id); },
            isDescendant(phase, ancestorTempId) {
                let current = phase; const seen = new Set();
                while (current?.parent_id && !seen.has(current.temp_id)) {
                    seen.add(current.temp_id);
                    if (current.parent_id === ancestorTempId) return true;
                    current = this.byTempId(current.parent_id);
                }
                return false;
            },
            subtreeEnd(index) {
                let end = index;
                for (let i = index + 1; i < this.phases.length; i++) {
                    if (this.isDescendant(this.phases[i], this.phases[index].temp_id)) { end = i; } else { break; }
                }
                return end;
            },
            add() { this.phases.push({ id: null, temp_id: 'new_' + Date.now() + Math.random(), parent_id: null, name: '', format: '', order: this.phases.length + 1 }); },
            remove(index) {
                const phase = this.phases[index];
                const toRemove = new Set([phase.temp_id]);
                let changed = true;
                while (changed) {
                    changed = false;
                    for (const p of this.phases) {
                        if (! toRemove.has(p.temp_id) && p.parent_id && toRemove.has(p.parent_id)) { toRemove.add(p.temp_id); changed = true; }
                    }
                }
                this.phases = this.phases.filter(p => ! toRemove.has(p.temp_id));
            },
            indent(index) {
                if (index === 0) return;
                const phase = this.phases[index], above = this.phases[index - 1];
                phase.parent_id = (phase.parent_id !== above.parent_id) ? (above.parent_id ?? null) : above.temp_id;
            },
            outdent(index) {
                const phase = this.phases[index];
                if (! phase.parent_id) return;
                const parent = this.byTempId(phase.parent_id);
                phase.parent_id = parent?.parent_id ?? null;
            },
            moveUp(index) {
                if (index === 0) return;
                const end = this.subtreeEnd(index);
                const [prev] = this.phases.splice(index - 1, 1);
                this.phases.splice(end, 0, prev);
            },
            moveDown(index) {
                const end = this.subtreeEnd(index);
                if (end >= this.phases.length - 1) return;
                const nextEnd = this.subtreeEnd(end + 1);
                const block = this.phases.splice(end + 1, nextEnd - end);
                this.phases.splice(index, 0, ...block);
            },
         }" class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.tournaments.phases.title') }}</h2>
            <button type="button" @click="add()"
                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                + {{ __('admin.tournaments.phases.add') }}
            </button>
        </div>

        <template x-for="(phase, index) in phases" :key="phase.temp_id">
            <div class="rounded-lg border p-3 transition-all"
                 :class="phase.parent_id ? 'border-blue-500/30 bg-blue-500/[0.03]' : 'border-white/10 bg-white/5'"
                 :style="{ marginLeft: (depth(phase) * 1.5) + 'rem' }">
                <input type="hidden" :name="'phases['+index+'][id]'" x-model="phase.id">
                <input type="hidden" :name="'phases['+index+'][parent_id]'" :value="phase.parent_id ? phases.findIndex(p => p.temp_id === phase.parent_id) : ''">
                <input type="hidden" :name="'phases['+index+'][order]'" :value="index + 1">

                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/5 text-[10px] font-black text-gray-400" x-text="index + 1"></span>

                    <input type="text" :name="'phases['+index+'][name]'" x-model="phase.name" placeholder="{{ __('admin.tournaments.phases.name') }}" required
                           class="flex-1 min-w-0 bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-1.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

                    <select :name="'phases['+index+'][format]'" x-model="phase.format"
                            class="w-32 shrink-0 bg-[#0a0a0a] border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                        <option value="">—</option>
                        <option value="bracket">{{ __('admin.tournaments.phases.format_options.bracket') }}</option>
                        <option value="round_robin">{{ __('admin.tournaments.phases.format_options.round_robin') }}</option>
                        <option value="swiss">{{ __('admin.tournaments.phases.format_options.swiss') }}</option>
                    </select>

                    <div class="flex shrink-0 gap-0.5">
                        <button type="button" @click="outdent(index)" :disabled="! phase.parent_id" title="{{ __('admin.tournaments.phases.outdent') }}"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 disabled:opacity-20 disabled:pointer-events-none">&larr;</button>
                        <button type="button" @click="indent(index)" :disabled="index === 0" title="{{ __('admin.tournaments.phases.indent') }}"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 disabled:opacity-20 disabled:pointer-events-none">&rarr;</button>
                        <button type="button" @click="moveUp(index)" :disabled="index === 0" title="{{ __('admin.tournaments.phases.move_up') }}"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 disabled:opacity-20 disabled:pointer-events-none">&uarr;</button>
                        <button type="button" @click="moveDown(index)" :disabled="subtreeEnd(index) >= phases.length - 1" title="{{ __('admin.tournaments.phases.move_down') }}"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 disabled:opacity-20 disabled:pointer-events-none">&darr;</button>
                        <button type="button" @click="remove(index)" title="{{ __('admin.tournaments.phases.remove') }}"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10">&times;</button>
                    </div>
                </div>
            </div>
        </template>

        <p class="text-[11px] text-gray-500 text-center py-4" x-show="phases.length === 0">{{ __('admin.tournaments.phases.empty') }}</p>
    </div>
</div>
