{{--
    GC-Stats — Roster entry card

    One grid cell of x-roster-panel: an entity-picker (type/field set by the
    panel's $pickerType/$pivotField — player when editing a team's roster,
    team when editing a player's team history) pre-selected with the row's
    counterpart entity, so the assignment can be reassigned, plus role/
    joined/left fields and a client-side "delete" toggle. Nothing here hits
    the server on its own — the whole grid (current + history) is submitted
    together by the panel's single sync form, so removing a card just hides
    it (via x-if, dropping its inputs from the submit) until Save is pressed.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3"
     x-data="{ removed: false }">
    <template x-if="!removed">
        <div class="space-y-3">
            <input type="hidden" name="entries[{{ $index }}][id]" value="{{ $entry->id }}">

            <livewire:entity-picker
                :type="$pickerType"
                :name="'entries['.$index.']['.$pivotField.']'"
                :selected="$entry->{$pivotField}"
                :key="'roster-entry-'.$pivotField.'-'.$entry->id"
                thumb-size="w-16 h-10"
            />

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $roleLabel }}</label>
                <select name="entries[{{ $index }}][role]" aria-label="{{ $roleLabel }}"
                        class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($entry->role === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $joinedAtLabel }}</label>
                <input type="date" name="entries[{{ $index }}][joined_at]" value="{{ $entry->joined_at }}" aria-label="{{ $joinedAtLabel }}"
                       class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $leftAtLabel }}</label>
                <input type="date" name="entries[{{ $index }}][left_at]" value="{{ $entry->left_at }}" aria-label="{{ $leftAtLabel }}"
                       class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
            </div>

            <button type="button"
                    @click="if (confirm(@js($removeConfirmBody($entry)))) removed = true"
                    class="w-full font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                {{ $removeLabel }}
            </button>
        </div>
    </template>

    <template x-if="removed">
        <div class="flex items-center justify-between gap-2 py-2">
            <span class="text-xs text-gray-500 line-through truncate">{{ $entry->player_handle ?? $entry->team_name ?? '' }}</span>
            <button type="button" @click="removed = false"
                    class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-2 py-1 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('team.roster.undo') }}
            </button>
        </div>
    </template>
</div>
