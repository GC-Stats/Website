{{--
    GC-Stats — XP entry card (event-scoped)

    One row of x-admin.xp-event-panel — used on a Tournament/Match admin
    page, where the event (assignable) is fixed by the panel's scope. Each
    row picks who did it (a staff member) and which team/org they
    represented + their role for this entry, plus a small free-form
    "metadata" field. Nothing hits the server until the whole panel's single
    Save is pressed — removing a card just hides it (dropping its inputs
    from the submit), same pattern as roster-entry-card.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['entry', 'index', 'teamRoles', 'orgRoles', 'removeConfirmBody'])

<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3"
     x-data="{ removed: false, role: {{ \Illuminate\Support\Js::from($entry->role) }} }">
    <template x-if="!removed">
        <div class="space-y-3">
            <input type="hidden" name="entries[{{ $index }}][id]" value="{{ $entry->id }}">

            <livewire:entity-picker
                type="staff"
                :name="'entries['.$index.'][staff_id]'"
                :selected="$entry->staff_id"
                :key="'xp-event-staff-'.$entry->id"
                thumb-size="w-16 h-10"
            />

            <x-partials.xp-team-or-org
                :index="$index"
                :prefix="'entries['.$index.']'"
                :team-id="$entry->team_id"
                :organization-id="$entry->organization_id"
                :role="$entry->role"
                :team-roles="$teamRoles"
                :org-roles="$orgRoles"
                :role-label="__('admin.staff_experience.role_label')"
                :representing-label="__('admin.staff_experience.representing_label')"
            />

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
            <span class="text-xs text-gray-500 line-through truncate">{{ $entry->staff?->handle ?? $entry->team?->name ?? $entry->organization?->name ?? '' }}</span>
            <button type="button" @click="removed = false"
                    class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-2 py-1 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('team.roster.undo') }}
            </button>
        </div>
    </template>
</div>
