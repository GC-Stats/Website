{{--
    GC-Stats — Staff assignments panel

    Shared by the admin Tournament/Match/Team pages: declares "this staff
    member did this role for this entity" (App\Models\StaffAssignment).
    List with a per-row delete (mirrors x-admin.role-members-panel's
    removeUrl-per-row callback convention) plus an add form using the
    generic entity-picker for staff (and, when $showTeamPicker is true,
    for the team the staff represented — only relevant when the assignable
    entity itself isn't a Team).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'assignments',
    'storeUrl',
    'removeUrl',
    'roles',
    'title',
    'addLabel',
    'roleLabel',
    'teamLabel' => null,
    'startedAtLabel',
    'endedAtLabel',
    'saveLabel',
    'removeLabel',
    'removeConfirmBody',
    'emptyLabel',
    'showTeamPicker' => true,
    'headingTag' => 'h2',
])

<div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
    <{{ $headingTag }} class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</{{ $headingTag }}>

    <div class="space-y-2">
        @forelse ($assignments as $assignment)
            <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                <div class="min-w-0">
                    <p class="text-sm text-white font-semibold truncate">
                        {{ $assignment->staff?->handle }}
                        <span class="text-gray-500 font-normal">— {{ \App\Helpers\StaffRoleLabel::label($assignment->role) }}</span>
                    </p>
                    <p class="text-xs text-gray-500 truncate">
                        @if ($assignment->team)
                            {{ $assignment->team->name }} ·
                        @endif
                        {{ \App\Helpers\PivotDate::format($assignment->started_at, 'Y-m-d') ?? '—' }}
                        @if ($assignment->ended_at)
                            – {{ \App\Helpers\PivotDate::format($assignment->ended_at, 'Y-m-d') }}
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ $removeUrl($assignment) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="$removeLabel"
                        :body="$removeConfirmBody($assignment)"
                        :trigger-label="$removeLabel"
                        :submit-label="$removeLabel"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            </div>
        @empty
            <p class="text-xs text-gray-500">{{ $emptyLabel }}</p>
        @endforelse
    </div>

    <div x-data="{ open: false }">
        <button type="button" @click="open = !open"
                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
            {{ $addLabel }}
        </button>

        <form x-show="open" x-cloak method="POST" action="{{ $storeUrl }}" class="mt-3 space-y-3 bg-black/20 border border-white/10 rounded-lg p-4">
            @csrf

            <livewire:entity-picker type="staff" name="staff_id" :label="null" thumb-size="w-8 h-8" />

            @if ($showTeamPicker)
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ $teamLabel }}</label>
                    <livewire:entity-picker type="team" name="team_id" :label="null" thumb-size="w-8 h-5" />
                </div>
            @endif

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ $roleLabel }}</label>
                <x-styled-select name="role" :options="$roles" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ $startedAtLabel }}</label>
                    <input type="date" name="started_at" class="w-full bg-[#050505] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ $endedAtLabel }}</label>
                    <input type="date" name="ended_at" class="w-full bg-[#050505] border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                </div>
            </div>

            <button type="submit"
                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                {{ $saveLabel }}
            </button>
        </form>
    </div>
</div>
