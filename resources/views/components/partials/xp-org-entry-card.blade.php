{{--
    GC-Stats — XP entry card (organization-dashboard-scoped)

    One row of x-admin.xp-org-panel — used from an organization's own
    dashboard, on one tournament/match's XP editor. Unlike
    xp-event-entry-card, there's no team/org toggle: every entry from here
    represents this organization (organization_id is forced server-side by
    the panel's scope), so the only choice per row is *who* it's for — one
    of the org's own staff, or the organization itself declaring the XP on
    its own behalf (no individual attached).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['entry', 'index', 'orgRoles', 'removeConfirmBody'])

<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3"
     x-data="{ removed: false, forOrg: {{ \Illuminate\Support\Js::from(! $entry->staff_id) }}, role: {{ \Illuminate\Support\Js::from($entry->role) }} }">
    <template x-if="!removed">
        <div class="space-y-3">
            <input type="hidden" name="entries[{{ $index }}][id]" value="{{ $entry->id }}">

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
                    :selected="$entry->staff_id"
                    :key="'xp-org-staff-'.$entry->id"
                    thumb-size="w-16 h-10"
                />
            </template>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.staff_experience.role_label') }}</label>
                <x-styled-select name="entries[{{ $index }}][role]" x-model="role" :selected="$entry->role" :options="$orgRoles" searchable />
            </div>

            <div x-show="role === 'caster'" x-cloak>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.staff_experience.metadata_label') }}</label>
                <x-styled-select :name="'entries['.$index.'][metadata][language]'" :selected="$entry->metadata['language'] ?? null" :options="['' => __('admin.staff_experience.metadata_placeholder')] + \App\Support\StaffRoleMetadata::LANGUAGES" searchable />
            </div>

            <button type="button"
                    @click="if (confirm(@js($removeConfirmBody($entry)))) removed = true"
                    class="w-full font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                {{ __('admin.staff_experience.remove_label') }}
            </button>
        </div>
    </template>

    <template x-if="removed">
        <div class="flex items-center justify-between gap-2 py-2">
            <span class="text-xs text-gray-500 line-through truncate">{{ $entry->staff?->handle ?? __('admin.staff_experience.the_organization') }}</span>
            <button type="button" @click="removed = false"
                    class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-2 py-1 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('team.roster.undo') }}
            </button>
        </div>
    </template>
</div>
