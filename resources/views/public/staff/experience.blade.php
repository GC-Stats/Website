{{--
    GC-Stats — Staff experience page

    Every declared XP entry (App\Models\StaffAssignment), grouped by
    tournament (most recent first, via the tournament's own start_date —
    see StaffAssignment::tournamentStartDate()), with a per-role summary
    (count + earliest date) linking into the per-role sub-page
    (staff.experience.role). See Public\StaffController::renderExperience().

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', $roleFilter
    ? __('staff.experience.title_role', ['staff' => $staffMember->handle, 'role' => \App\Helpers\StaffRoleLabel::label($roleFilter, $staffMember->pronouns)])
    : __('staff.experience.title', ['staff' => $staffMember->handle]))
@section('canonical', $roleFilter
    ? route('staff.experience.role', [$staffMember->id, str($staffMember->handle)->slug(), \Illuminate\Support\Str::slug($roleFilter)])
    : route('staff.experience', [$staffMember->id, str($staffMember->handle)->slug()]))
@section('og_image', $staffMember->photo ?: asset('web-app-manifest-512x512.png'))

@php
    $assignmentLabel = function ($assignment) {
        return match ($assignment->assignable_type) {
            'match' => $assignment->assignable
                ? \App\Support\MatchDisplay::teamShortName($assignment->assignable->teamA, $assignment->assignable->status).' VS '.\App\Support\MatchDisplay::teamShortName($assignment->assignable->teamB, $assignment->assignable->status)
                : null,
            default => $assignment->assignable?->name,
        };
    };
    $assignmentSubtitle = fn ($assignment) => $assignment->assignable_type === 'match' ? $assignment->assignable?->round_name : null;
    $assignmentUrl = function ($assignment) {
        return match ($assignment->assignable_type) {
            'tournament' => $assignment->assignable ? route('tournaments.show', [$assignment->assignable->id, str($assignment->assignable->name)->slug()]) : null,
            'match' => $assignment->assignable ? route('match.show', $assignment->assignable->id) : null,
            default => null,
        };
    };
@endphp

@section('content')
    @include('public.staff.header')

    @if ($careerStats && $careerStats['tournaments'] > 0)
        <p class="text-xs text-gray-400 mb-6">
            {{ trans_choice('staff.experience.career.tournaments', $careerStats['tournaments'], ['count' => $careerStats['tournaments']]) }}
            <span class="text-gray-700 mx-1">&middot;</span>
            {{ trans_choice('staff.experience.career.roles', $careerStats['roles'], ['count' => $careerStats['roles']]) }}
            @if ($careerStats['since'])
                <span class="text-gray-700 mx-1">&middot;</span>
                {{ __('staff.experience.career.since', ['year' => $careerStats['since']->format('Y')]) }}
            @endif
        </p>
    @endif

    @if ($summary->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
            @foreach ($summary as $entry)
                <a href="{{ route('staff.experience.role', [$staffMember->id, str($staffMember->handle)->slug(), $entry['slug']]) }}"
                   class="group block bg-[#050505] border border-white/5 rounded-sm p-4 transition-all duration-300 hover:border-[var(--brand-yellow)]/30 {{ $roleFilter === $entry['role'] ? 'border-[var(--brand-yellow)]/50' : '' }}">
                    <div class="w-1.5 h-1.5 rounded-full mb-2 {{ \App\Helpers\StaffRoleLabel::barClass($entry['role']) }}"></div>
                    <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors">{{ \App\Helpers\StaffRoleLabel::label($entry['role'], $staffMember->pronouns) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">
                        {{ trans_choice('staff.experience.count', $entry['count'], ['count' => $entry['count']]) }}
                    </p>
                    @if ($entry['since'])
                        <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">
                            {{ __('staff.experience.since', ['year' => $entry['since']->format('Y')]) }}
                        </p>
                    @endif
                </a>
            @endforeach
        </div>

        @if ($roleFilter)
            <a href="{{ route('staff.experience', [$staffMember->id, str($staffMember->handle)->slug()]) }}"
               class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gc-yellow hover:text-white transition mb-4">
                &larr; {{ __('staff.experience.clear_filter') }}
            </a>

            @if ($roleStats)
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                    <div class="bg-[#050505] border border-white/5 rounded-sm p-3">
                        <p class="text-lg font-black text-white">{{ $roleStats['total'] }}</p>
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ __('staff.experience.stats.total') }}</p>
                    </div>
                    <div class="bg-[#050505] border border-white/5 rounded-sm p-3">
                        <p class="text-lg font-black text-white">{{ $roleStats['tournaments'] }}</p>
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ __('staff.experience.stats.tournaments') }}</p>
                    </div>
                    @if ($roleStats['matches'] > 0)
                        <div class="bg-[#050505] border border-white/5 rounded-sm p-3">
                            <p class="text-lg font-black text-white">{{ $roleStats['matches'] }}</p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ __('staff.experience.stats.matches') }}</p>
                        </div>
                    @endif
                    @if ($roleStats['represented']->isNotEmpty())
                        <div class="bg-[#050505] border border-white/5 rounded-sm p-3">
                            <p class="text-lg font-black text-white">{{ $roleStats['represented']->count() }}</p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">
                                {{ __($roleStats['representedIsTeam'] ? 'staff.experience.stats.represented_team' : 'staff.experience.stats.represented_org') }}
                            </p>
                        </div>
                    @endif
                    @if ($roleStats['firstYear'])
                        <div class="bg-[#050505] border border-white/5 rounded-sm p-3">
                            <p class="text-lg font-black text-white">
                                {{ $roleStats['firstYear'] === $roleStats['lastYear']
                                    ? __('staff.experience.stats.active_single_year', ['year' => $roleStats['firstYear']])
                                    : __('staff.experience.stats.active_range', ['first' => $roleStats['firstYear'], 'last' => $roleStats['lastYear']]) }}
                            </p>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ __('staff.experience.stats.active') }}</p>
                        </div>
                    @endif
                </div>

                @if ($roleStats['represented']->isNotEmpty())
                    <div class="mb-4">
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-1.5">
                            {{ __($roleStats['representedIsTeam'] ? 'staff.experience.stats.represented_team' : 'staff.experience.stats.represented_org') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($roleStats['represented'] as $row)
                                @php
                                    $entity = $row['entity'];
                                    $entityUrl = $roleStats['representedIsTeam']
                                        ? route('teams.show', [$entity->id, $entity->routeSlug()])
                                        : route('organizations.show', [$entity->id, $entity->routeSlug()]);
                                @endphp
                                <a href="{{ $entityUrl }}"
                                   class="group flex items-center gap-2 bg-[#050505] border border-white/5 rounded-sm pl-1.5 pr-2.5 py-1.5 hover:border-[var(--brand-yellow)]/30 transition">
                                    @if ($entity->logo)
                                        <img src="{{ $entity->logo }}" alt="" class="w-5 h-5 rounded-sm object-cover shrink-0">
                                    @endif
                                    <span class="text-[10px] font-bold text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate max-w-[10rem]">{{ $entity->name }}</span>
                                    @if ($row['count'] > 1)
                                        <span class="text-[9px] text-gray-500 font-bold shrink-0">&times;{{ $row['count'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($roleStats['categories']->isNotEmpty())
                    <div class="mb-4">
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-1.5">{{ __('staff.experience.stats.categories_heading') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($roleStats['categories'] as $category => $count)
                                <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-1 rounded-sm bg-white/5 text-gray-300">
                                    {{ $category }} <span class="text-gray-500">&times;{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($roleStats['languages']->isNotEmpty())
                    <div class="mb-4">
                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-1.5">{{ __('staff.experience.stats.languages_heading') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($roleStats['languages'] as $language => $count)
                                <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-1 rounded-sm bg-white/5 text-gray-300">
                                    {{ $language }} <span class="text-gray-500">&times;{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endif
    @endif

    <div class="flex items-center gap-2 mb-3">
        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">
            {{ $roleFilter ? \App\Helpers\StaffRoleLabel::label($roleFilter, $staffMember->pronouns) : __('staff.experience.heading') }}
        </span>
        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
    </div>

    @if ($groups->isEmpty())
        <p class="text-xs text-gray-500">{{ __('staff.experience.empty') }}</p>
    @else
        <div class="space-y-6">
            @foreach ($groups as $group)
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        @if ($group['tournament'])
                            <a href="{{ route('tournaments.show', [$group['tournament']->id, str($group['tournament']->name)->slug()]) }}"
                               class="text-sm font-black text-white hover:text-[var(--brand-yellow)] transition truncate">
                                {{ $group['tournament']->name }}
                            </a>
                        @else
                            <span class="text-sm font-black text-gray-500">{{ __('staff.experience.unknown_tournament') }}</span>
                        @endif
                        @if ($group['date'])
                            <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest shrink-0">
                                {{ $group['date']->format('M Y') }}
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($group['entries'] as $assignment)
                            @php $url = $assignmentUrl($assignment); @endphp
                            <div class="tournament-card flex bg-[#050505] {{ $url ? 'hover:bg-bg-main hover:border-[var(--brand-yellow)]/30' : '' }} border border-white/5 rounded-sm overflow-hidden transition-all duration-300 shadow-lg">
                                <div class="w-1 shrink-0 {{ \App\Helpers\StaffRoleLabel::barClass($assignment->role) }}"></div>
                                <div class="flex items-center gap-4 p-3 min-w-0 flex-1">
                                    <div class="flex-1 min-w-0">
                                        @if ($url)
                                            <a href="{{ $url }}" class="group">
                                                <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">
                                                    {{ $assignmentLabel($assignment) ?? '—' }}
                                                </p>
                                            </a>
                                        @else
                                            <p class="text-sm font-black tracking-tight text-white truncate">{{ $assignmentLabel($assignment) ?? '—' }}</p>
                                        @endif
                                        @if ($assignmentSubtitle($assignment))
                                            <p class="text-[10px] text-gray-500 truncate">{{ $assignmentSubtitle($assignment) }}</p>
                                        @endif
                                        <div class="mt-1 flex flex-wrap items-center gap-2 min-w-0">
                                            <span class="shrink-0 text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm {{ \App\Helpers\StaffRoleLabel::badgeClass($assignment->role) }}">
                                                {{ \App\Helpers\StaffRoleLabel::label($assignment->role, $staffMember->pronouns) }}
                                            </span>
                                            @if ($assignment->team)
                                                <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest truncate">
                                                    {{ __('staff.experience.representing', ['team' => $assignment->team->name]) }}
                                                </span>
                                            @elseif ($assignment->organization)
                                                <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest truncate">
                                                    {{ __('staff.experience.representing', ['team' => $assignment->organization->name]) }}
                                                </span>
                                            @endif
                                            @if ($assignment->metadata['language'] ?? null)
                                                <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest truncate">
                                                    {{ \App\Support\StaffRoleMetadata::LANGUAGES[$assignment->metadata['language']] ?? $assignment->metadata['language'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
