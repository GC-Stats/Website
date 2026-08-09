{{--
    GC-Stats — Organization overview page

    Displays the organization's profile: header, current staff roster, and
    former staff. No matches/news/achievements yet — see the header
    partial's docblock and OrganizationController's for what's deferred.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('organization.title.index', ['organization' => $organization->name]))
@section('canonical', route('organizations.show', [$organization->id, $organization->routeSlug()]))
@section('og_image', $organization->logo)

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $organization->name,
    'logo' => $organization->logo,
    'url' => route('organizations.show', [$organization->id, $organization->routeSlug()]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@section('content')
    @include('public.organization.header')

    <div class="flex items-center gap-2 mb-3">
        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('organization.staff') }}</span>
        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
    </div>

    @if ($currentStaff->isEmpty())
        <p class="text-xs text-gray-500 mb-8">{{ __('organization.empty.staff') }}</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-8">
            @foreach ($currentStaff as $entry)
                <a href="{{ route('staff.show', [$entry->staff_id, str($entry->staff_handle)->slug()]) }}" class="group block">
                    <div class="tournament-card flex bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                        <div class="w-1 shrink-0 {{ \App\Helpers\StaffRoleLabel::barClass($entry->role) }}"></div>
                        <div class="flex items-center gap-4 p-3 min-w-0 flex-1">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">
                                    {{ $entry->staff_handle }}
                                </p>
                                <div class="mt-1 flex items-center gap-2 min-w-0">
                                    <span class="shrink-0 text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm {{ \App\Helpers\StaffRoleLabel::badgeClass($entry->role) }}">
                                        {{ \App\Helpers\StaffRoleLabel::label($entry->role, $entry->staff_pronouns) }}
                                    </span>
                                    <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest truncate">
                                        Since {{ \App\Helpers\PivotDate::format($entry->joined_at, 'm/Y') ?? 'UNKNOWN' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    @if ($formerStaff->isNotEmpty())
        <div class="flex items-center gap-2 mb-3">
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('organization.old_staff') }}</span>
            <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($formerStaff as $entry)
                <a href="{{ route('staff.show', [$entry->staff_id, str($entry->staff_handle)->slug()]) }}" class="group block">
                    <div class="tournament-card flex bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                        <div class="w-1 shrink-0 bg-gray-500"></div>
                        <div class="flex items-center gap-4 p-3 min-w-0 flex-1">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">
                                    {{ $entry->staff_handle }}
                                </p>
                                <div class="mt-1 flex items-center gap-2 min-w-0">
                                    <span class="shrink-0 text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm bg-gray-500/10 text-gray-400">
                                        {{ \App\Helpers\StaffRoleLabel::label($entry->role, $entry->staff_pronouns) }}
                                    </span>
                                    <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest truncate">
                                        {{ \App\Helpers\PivotDate::format($entry->joined_at, 'm/Y') ?? 'UNKNOWN' }}
                                        - {{ \App\Helpers\PivotDate::format($entry->left_at, 'm/Y') ?? 'UNKNOWN' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
