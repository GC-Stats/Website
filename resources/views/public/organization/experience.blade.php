{{--
    GC-Stats — Organization experience page

    XP entries the organization itself holds (staff_id null — "Org X
    organized Tournament Y", not an individual staff member's own XP),
    grouped by tournament. See Public\OrganizationController::experience().

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('organization.title.index', ['organization' => $organization->name]))
@section('canonical', route('organizations.experience', [$organization->id, $organization->routeSlug()]))
@section('og_image', $organization->logo)

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
    @include('public.organization.header')

    <div class="flex items-center gap-2 mb-3">
        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('organization.experience.heading') }}</span>
        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
    </div>

    @if ($groups->isEmpty())
        <p class="text-xs text-gray-500">{{ __('organization.experience.empty') }}</p>
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
                            <span class="text-sm font-black text-gray-500">{{ __('organization.experience.unknown_tournament') }}</span>
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
                                                {{ \App\Helpers\StaffRoleLabel::label($assignment->role) }}
                                            </span>
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
