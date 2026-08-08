{{--
    GC-Stats — XP entry: team/org toggle + role picker

    Shared by xp-event-entry-card / xp-staff-entry-card. Exactly one of
    team_id/organization_id is required per StaffAssignment entry, and the
    role list depends on which is picked (StaffRoles::TEAM_ROLES vs
    ::ORG_ROLES) — toggling between the two swaps both the entity-picker and
    the role <select> via x-if (not x-show), so only the active side's
    inputs are ever present in the submitted form.

    Expects: $index, $prefix (form field prefix, e.g. "entries[3]"),
    $teamId, $organizationId, $role (current values, for edit), $teamRoles,
    $orgRoles, $roleLabel, $representingLabel.

    Both role <select>s are x-model-bound to the *caller's* `role` Alpine
    property (not a local one — this component only declares `mode`, so
    `x-model="role"` resolves up the scope chain to whatever ancestor
    x-data owns it), so a sibling metadata block outside this component can
    react to the picked role (e.g. show a caster-only field).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['index', 'prefix', 'teamId' => null, 'organizationId' => null, 'role' => null, 'teamRoles', 'orgRoles', 'roleLabel', 'representingLabel'])

<div x-data="{ mode: {{ \Illuminate\Support\Js::from($organizationId ? 'org' : 'team') }} }">
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $representingLabel }}</label>
    <div class="flex gap-1.5 mb-2">
        <button type="button" @click="mode = 'team'"
                :class="mode === 'team' ? 'bg-gc-yellow text-black' : 'bg-white/5 text-gray-400 hover:bg-white/10'"
                class="flex-1 font-bold uppercase text-[9px] tracking-widest px-2 py-1.5 rounded-sm transition">
            {{ __('admin.staff_experience.team') }}
        </button>
        <button type="button" @click="mode = 'org'"
                :class="mode === 'org' ? 'bg-gc-yellow text-black' : 'bg-white/5 text-gray-400 hover:bg-white/10'"
                class="flex-1 font-bold uppercase text-[9px] tracking-widest px-2 py-1.5 rounded-sm transition">
            {{ __('admin.staff_experience.org') }}
        </button>
    </div>

    <template x-if="mode === 'team'">
        <div class="space-y-2">
            <livewire:entity-picker
                type="team"
                :name="$prefix.'[team_id]'"
                :selected="$teamId"
                :key="'xp-team-'.$index"
                thumb-size="w-16 h-10"
            />
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $roleLabel }}</label>
                <x-styled-select :name="$prefix.'[role]'" x-model="role" :selected="$role" :options="$teamRoles" searchable />
            </div>
        </div>
    </template>

    <template x-if="mode === 'org'">
        <div class="space-y-2">
            <livewire:entity-picker
                type="organization"
                :name="$prefix.'[organization_id]'"
                :selected="$organizationId"
                :key="'xp-org-'.$index"
                thumb-size="w-16 h-10"
            />
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ $roleLabel }}</label>
                <x-styled-select :name="$prefix.'[role]'" x-model="role" :selected="$role" :options="$orgRoles" searchable />
            </div>
        </div>
    </template>
</div>
