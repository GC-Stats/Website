{{--
    GC-Stats — XP new entry card (staff-scoped, blank)

    Blank-row counterpart to xp-staff-entry-card — see roster-new-entry-card
    for why this is a pre-mounted slot rather than an x-for row.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['index', 'slot', 'teamRoles', 'orgRoles'])

<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3" x-data="{ removed: false, role: null }">
    <template x-if="!removed">
        <div class="space-y-3">
            <livewire:entity-picker
                type="tournament"
                :name="'entries['.$index.'][tournament_id]'"
                :key="'xp-staff-new-tournament-'.$slot"
                thumb-size="w-16 h-10"
            />

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.staff_experience.match_label') }}</label>
                <input type="number" name="entries[{{ $index }}][match_id]"
                       class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
            </div>

            <x-partials.xp-team-or-org
                :index="$index"
                :prefix="'entries['.$index.']'"
                :team-roles="$teamRoles"
                :org-roles="$orgRoles"
                :role-label="__('admin.staff_experience.role_label')"
                :representing-label="__('admin.staff_experience.representing_label')"
            />

            <div x-show="role === 'caster'" x-cloak>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.staff_experience.metadata_label') }}</label>
                <x-styled-select :name="'entries['.$index.'][metadata][language]'" :options="['' => __('admin.staff_experience.metadata_placeholder')] + \App\Support\StaffRoleMetadata::LANGUAGES" searchable />
            </div>

            <button type="button" @click="removed = true"
                    class="w-full font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                {{ __('admin.staff_experience.remove_label') }}
            </button>
        </div>
    </template>
</div>
