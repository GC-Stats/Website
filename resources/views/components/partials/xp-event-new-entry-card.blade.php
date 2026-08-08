{{--
    GC-Stats — XP new entry card (event-scoped, blank)

    Blank-row counterpart to xp-event-entry-card, mounted on demand by
    x-admin.xp-event-panel's "add" button — see roster-new-entry-card's
    docblock for why this is a fixed pool of pre-mounted slots rather than
    an x-for (Livewire components can't be dynamically instantiated inside
    one).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['index', 'slot', 'teamRoles', 'orgRoles'])

<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3" x-data="{ removed: false, role: null }">
    <template x-if="!removed">
        <div class="space-y-3">
            <livewire:entity-picker
                type="staff"
                :name="'entries['.$index.'][staff_id]'"
                :key="'xp-event-new-staff-'.$slot"
                thumb-size="w-16 h-10"
            />

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
