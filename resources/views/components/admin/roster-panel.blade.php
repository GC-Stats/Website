{{--
    GC-Stats — Roster panel

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'current',
    'history',
    'syncUrl',
    'roles',
    'title',
    'historyTitle',
    'addLabel',
    'roleLabel',
    'joinedAtLabel',
    'leftAtLabel',
    'saveLabel',
    'removeLabel',
    'removeConfirmBody',
    'currentEmptyLabel',
    'historyEmptyLabel',
    'headingTag' => 'h3',
    'pickerType' => 'player',
    'pivotField' => 'player_id',
    'maxNewSlots' => 6,
])

@php
    $entryIndex = 0;
    // New-slot indices are reserved above every real entry's index up front
    // (current + history counts, known before either loop runs) so they
    // never collide regardless of render order — see roster-new-entry-card.
    $newSlotBase = $current->count() + $history->count();
@endphp

<div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
    <{{ $headingTag }} class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</{{ $headingTag }}>

    <form method="POST" action="{{ $syncUrl }}" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- New members: a fixed pool of pre-mounted entity-pickers, each
             individually shown/hidden — a Livewire component can't be
             instantiated dynamically from a plain Alpine x-for, so "add"
             reveals the next free slot in this pool (capped at
             $maxNewSlots) and each slot's own "remove" hides just that one.
             Nothing hits the server until the whole panel's Save is
             pressed, same as change-request.blade.php's roster section. --}}
        <div class="space-y-3" x-data="{
                activeSlots: [],
                addSlot() {
                    for (let i = 0; i < {{ $maxNewSlots }}; i++) {
                        if (! this.activeSlots.includes(i)) { this.activeSlots.push(i); break; }
                    }
                },
                removeSlot(i) { this.activeSlots = this.activeSlots.filter(slot => slot !== i); },
             }">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                @forelse ($current as $entry)
                    @include('components.partials.roster-entry-card', ['entry' => $entry, 'index' => $entryIndex])
                    @php $entryIndex++; @endphp
                @empty
                    <p class="text-xs text-gray-500 col-span-full" x-show="activeSlots.length === 0">{{ $currentEmptyLabel }}</p>
                @endforelse

                @for ($i = 0; $i < $maxNewSlots; $i++)
                    <template x-if="activeSlots.includes({{ $i }})">
                        @include('components.partials.roster-new-entry-card', ['index' => $newSlotBase + $i, 'slot' => $i])
                    </template>
                @endfor
            </div>

            <button type="button" @click="addSlot()" x-show="activeSlots.length < {{ $maxNewSlots }}"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ $addLabel }}
            </button>
        </div>

        <div class="pt-4 border-t border-border-subtle space-y-3">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ $historyTitle }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                @forelse ($history as $entry)
                    @include('components.partials.roster-entry-card', ['entry' => $entry, 'index' => $entryIndex])
                    @php $entryIndex++; @endphp
                @empty
                    <p class="text-xs text-gray-500 col-span-full">{{ $historyEmptyLabel }}</p>
                @endforelse
            </div>
        </div>

        <button type="submit"
                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
            {{ $saveLabel }}
        </button>
    </form>
</div>
