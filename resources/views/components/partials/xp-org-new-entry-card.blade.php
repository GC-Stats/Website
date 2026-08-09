{{--
    GC-Stats — XP new entry card (organization-dashboard-scoped, blank)

    Blank-row counterpart to xp-org-entry-card — see roster-new-entry-card
    for why this is a pre-mounted slot rather than an x-for row.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['index', 'slot', 'orgRoles'])

<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3" x-data="{ removed: false, forOrg: false, role: null }">
    <template x-if="!removed">
        <div class="space-y-3">
            <div class="flex gap-1.5">
                <button type="button" @click="forOrg = false"
                        :class="!forOrg ? 'bg-gc-yellow text-black' : 'bg-white/5 text-gray-400 hover:bg-white/10'"
                        class="flex-1 font-bold uppercase text-[9px] tracking-widest px-2 py-1.5 rounded-sm transition">
                    {{ __('admin.staff_experience.who_staff') }}
                </button>
                <button type="button" @click="forOrg = true"
                        :class="forOrg ? 'bg-gc-yellow text-black' : 'bg-white/5 text-gray-400 hover:bg-white/10'"
                        class="flex-1 font-bold uppercase text-[9px] tracking-widest px-2 py-1.5 rounded-sm transition">
                    {{ __('admin.staff_experience.the_organization') }}
                </button>
            </div>

            <template x-if="!forOrg">
                <livewire:entity-picker
                    type="staff"
                    :name="'entries['.$index.'][staff_id]'"
                    :key="'xp-org-new-staff-'.$slot"
                    thumb-size="w-16 h-10"
                />
            </template>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.staff_experience.role_label') }}</label>
                <x-styled-select name="entries[{{ $index }}][role]" x-model="role" :options="$orgRoles" searchable />
            </div>

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
