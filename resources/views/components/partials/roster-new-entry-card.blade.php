{{--
    GC-Stats — Roster new-entry card

    One pre-mounted "new member" slot for x-admin.roster-panel's add-slot
    pool (see the panel's activeSlots x-data): an unselected entity-picker
    plus role/joined_at fields, submitted as a plain entries[{{ $index }}]
    row alongside the existing cards — RosterService::save() inserts it
    since it has no [id]. "Remove" just hides the slot again via the
    panel's removeSlot(), nothing hits the server until Save is pressed.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3">
    <livewire:entity-picker
        :type="$pickerType"
        :name="'entries['.$index.']['.$pivotField.']'"
        :key="'roster-new-slot-'.$pickerType.'-'.$slot"
        thumb-size="w-16 h-10"
    />

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $roleLabel }}</label>
        <x-styled-select name="entries[{{ $index }}][role]" :options="$roles" />
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $joinedAtLabel }}</label>
        <input type="date" name="entries[{{ $index }}][joined_at]" value="{{ now()->format('Y-m-d') }}" aria-label="{{ $joinedAtLabel }}"
               class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
    </div>

    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $leftAtLabel }}</label>
        <input type="date" name="entries[{{ $index }}][left_at]" aria-label="{{ $leftAtLabel }}"
               class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
    </div>

    <button type="button" @click="removeSlot({{ $slot }})"
            class="w-full font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
        {{ $removeLabel }}
    </button>
</div>
