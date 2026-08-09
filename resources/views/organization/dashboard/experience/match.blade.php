{{--
    GC-Stats — Organization dashboard: match XP editor

    Bulk XP editor (x-admin.xp-org-panel) scoped to this org + match. See
    Organization\ExperienceController::match().

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', $organization->name.' — '.\App\Support\MatchDisplay::teamShortName($match->teamA, $match->status).' vs '.\App\Support\MatchDisplay::teamShortName($match->teamB, $match->status))

@section('content')
    <a href="{{ route('organization-dashboard.experience.tournaments.show', [$organization, $tournament]) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $tournament->name }}
    </a>

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-black uppercase tracking-tighter text-white">
                {{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }}
                <span class="text-gray-500">VS</span>
                {{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}
            </h1>
            @if ($match->round_name)
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">{{ $match->round_name }}</p>
            @endif
        </div>
    </div>

    <x-admin.xp-org-panel
        :entries="$entries"
        :sync-url="route('organization-dashboard.experience.matches.sync', [$organization, $tournament, $match])"
        :title="__('admin.staff_experience.title')"
        :add-label="__('admin.staff_experience.add')"
        :save-label="__('admin.staff_experience.save')"
        :empty-label="__('admin.staff_experience.empty')"
        :remove-confirm-body="fn ($entry) => __('admin.staff_experience.remove_confirm', ['staff' => $entry->staff?->handle ?? $organization->name])"
    />
@endsection
