{{--
    GC-Stats — XP entry card (staff-scoped)

    One row of x-admin.xp-staff-panel — used on a Staff member's own admin
    page, where the holder (staff_id) is fixed by the panel's scope. Each
    row picks the tournament (searchable entity-picker) and, optionally, a
    specific match within it by id — matches aren't independently
    searchable (see App\Services\SearchService), so this is a plain numeric
    field rather than a picker; the match id is visible on that match's own
    admin page. Plus which team/org was represented + role, and metadata.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['entry', 'index', 'teamRoles', 'orgRoles', 'removeConfirmBody'])

@php
    $tournamentId = $entry->assignable_type === 'tournament' ? $entry->assignable_id : $entry->assignable?->tournament_id;
    $matchId = $entry->assignable_type === 'match' ? $entry->assignable_id : null;
@endphp

<div class="bg-[#050505] border border-border-subtle rounded-sm p-3 space-y-3"
     x-data="{ removed: false, role: {{ \Illuminate\Support\Js::from($entry->role) }} }">
    <template x-if="!removed">
        <div class="space-y-3">
            <input type="hidden" name="entries[{{ $index }}][id]" value="{{ $entry->id }}">

            <livewire:entity-picker
                type="tournament"
                :name="'entries['.$index.'][tournament_id]'"
                :selected="$tournamentId"
                :key="'xp-staff-tournament-'.$entry->id"
                thumb-size="w-16 h-10"
            />

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.staff_experience.match_label') }}</label>
                <input type="number" name="entries[{{ $index }}][match_id]" value="{{ $matchId }}"
                       class="w-full bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
            </div>

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
            <span class="text-xs text-gray-500 line-through truncate">{{ $entry->team?->name ?? $entry->organization?->name ?? '' }}</span>
            <button type="button" @click="removed = false"
                    class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-2 py-1 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('team.roster.undo') }}
            </button>
        </div>
    </template>
</div>
