{{--
    GC-Stats — Organization dashboard: tournament XP editor

    Bulk XP editor (x-admin.xp-org-panel) scoped to this org + tournament,
    plus a list of the tournament's matches to drill into for match-level
    entries. See Organization\ExperienceController::tournament().

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', $organization->name.' — '.$tournament->name)

@section('content')
    <a href="{{ route('organization-dashboard.experience.index', $organization) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.staff_experience.active_tournaments_title') }}
    </a>

    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-xl font-black uppercase tracking-tighter text-white">{{ $tournament->name }}</h1>
    </div>

    <div class="space-y-6">
        <x-admin.xp-org-panel
            :entries="$entries"
            :sync-url="route('organization-dashboard.experience.tournaments.sync', [$organization, $tournament])"
            :title="__('admin.staff_experience.title')"
            :add-label="__('admin.staff_experience.add')"
            :save-label="__('admin.staff_experience.save')"
            :empty-label="__('admin.staff_experience.empty')"
            :remove-confirm-body="fn ($entry) => __('admin.staff_experience.remove_confirm', ['staff' => $entry->staff?->handle ?? $organization->name])"
        />

        <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.staff_experience.matches_title') }}</h2>

            @if ($matches->isEmpty())
                <p class="text-xs text-gray-500">{{ __('admin.staff_experience.active_tournaments_empty') }}</p>
            @else
                <div class="space-y-2">
                    @foreach ($matches as $match)
                        <a href="{{ route('organization-dashboard.experience.matches.show', [$organization, $tournament, $match]) }}"
                           class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3 hover:bg-white/10 transition">
                            <span class="min-w-0">
                                <span class="block text-sm text-white font-semibold truncate">
                                    {{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }}
                                    <span class="text-gray-500">VS</span>
                                    {{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}
                                </span>
                                @if ($match->round_name)
                                    <span class="block text-[10px] text-gray-500 truncate">{{ $match->round_name }}</span>
                                @endif
                            </span>
                            @if ($match->scheduled_at)
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest shrink-0">{{ $match->scheduled_at->format('d/m/Y') }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
