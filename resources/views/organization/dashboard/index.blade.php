{{--
    GC-Stats — Organization dashboard: overview

    A read-only summary of the organization (profile snapshot + headline
    counts) — editing lives on its own page (edit.blade.php). Deliberately
    light on "stats" for now (no XP/points system extended to organizations
    yet, no tournament/match participation wired in — see the roadmap's
    later steps); this page is built to grow more cards as those land.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', $organization->name)

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-4 min-w-0">
            @if ($organization->logo)
                <img src="{{ $organization->logo }}" alt="" class="w-14 h-14 object-contain border border-white/10 rounded-lg bg-black/40 p-2 shrink-0">
            @else
                <div class="w-14 h-14 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10 shrink-0">
                    <span class="text-lg font-black text-[var(--brand-yellow)]">{{ strtoupper(substr($organization->name, 0, 1)) }}</span>
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="text-xl font-black uppercase tracking-tight text-white truncate">{{ $organization->name }}</h1>
                @if (! empty($organization->types()))
                    <p class="text-xs text-gray-500">{{ implode(' · ', $organization->types()) }}</p>
                @endif
            </div>
        </div>

        @if ($canEdit)
            <a href="{{ route('organization-dashboard.edit', $organization) }}"
               class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                {{ __('organization.dashboard.edit.title') }}
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">{{ __('organization.dashboard.stats.current_staff') }}</p>
            <p class="text-2xl font-black text-white">{{ $currentStaffCount }}</p>
        </div>
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">{{ __('organization.dashboard.stats.former_staff') }}</p>
            <p class="text-2xl font-black text-white">{{ $formerStaffCount }}</p>
        </div>
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">{{ __('organization.dashboard.stats.roles') }}</p>
            <p class="text-2xl font-black text-white">{{ $rolesCount }}</p>
        </div>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-4">{{ __('admin.organizations.staff.title') }}</h2>

        @if ($currentStaff->isEmpty())
            <p class="text-xs text-gray-500">{{ __('admin.organizations.staff.current_empty') }}</p>
        @else
            <div class="space-y-2">
                @foreach ($currentStaff as $entry)
                    <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                        <span class="text-sm text-white font-semibold">{{ $entry->staff_handle }}</span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ \App\Helpers\StaffRoleLabel::label($entry->role, $entry->staff_pronouns) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
