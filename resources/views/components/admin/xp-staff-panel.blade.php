{{--
    GC-Stats — XP panel (staff-scoped)

    Bulk roster-style editor for one staff member's own staff_assignments —
    lives on their admin page next to organizations_panel/teams_panel. Same
    pre-mounted-slots pattern as x-admin.roster-panel / xp-event-panel.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'entries',
    'syncUrl',
    'title',
    'addLabel',
    'saveLabel',
    'emptyLabel',
    'removeConfirmBody',
    'maxNewSlots' => 8,
])

@php
    $teamRoles = \App\Helpers\StaffRoleLabel::options(\App\Support\StaffRoles::TEAM_ROLES);
    $orgRoles = \App\Helpers\StaffRoleLabel::options(\App\Support\StaffRoles::ORG_ROLES);
    $entryIndex = 0;
    $newSlotBase = $entries->count();
@endphp

<div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</h2>

    <form method="POST" action="{{ $syncUrl }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="space-y-3" x-data="{
                activeSlots: [],
                addSlot() {
                    for (let i = 0; i < {{ $maxNewSlots }}; i++) {
                        if (! this.activeSlots.includes(i)) { this.activeSlots.push(i); break; }
                    }
                },
             }">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @forelse ($entries as $entry)
                    @include('components.partials.xp-staff-entry-card', ['entry' => $entry, 'index' => $entryIndex, 'teamRoles' => $teamRoles, 'orgRoles' => $orgRoles, 'removeConfirmBody' => $removeConfirmBody])
                    @php $entryIndex++; @endphp
                @empty
                    <p class="text-xs text-gray-500 col-span-full" x-show="activeSlots.length === 0">{{ $emptyLabel }}</p>
                @endforelse

                @for ($i = 0; $i < $maxNewSlots; $i++)
                    <template x-if="activeSlots.includes({{ $i }})">
                        @include('components.partials.xp-staff-new-entry-card', ['index' => $newSlotBase + $i, 'slot' => $i, 'teamRoles' => $teamRoles, 'orgRoles' => $orgRoles])
                    </template>
                @endfor
            </div>

            <button type="button" @click="addSlot()" x-show="activeSlots.length < {{ $maxNewSlots }}"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ $addLabel }}
            </button>
        </div>

        <button type="submit"
                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
            {{ $saveLabel }}
        </button>
    </form>
</div>
