{{--
    GC-Stats — Staff overview page

    Displays the staff member's profile: header, current + former teams and
    organizations. No role history across tournaments/matches yet — see
    StaffController's docblock for what's deferred.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('staff.title.index', ['staff' => $staffMember->handle]))
@section('description', \Illuminate\Support\Str::limit(strip_tags($staffMember->bio ?? ''), 160) ?: __('staff.title.index', ['staff' => $staffMember->handle]))
@section('canonical', route('staff.show', [$staffMember->id, str($staffMember->handle)->slug()]))
@section('og_image', $staffMember->photo ?: asset('web-app-manifest-512x512.png'))

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $staffMember->handle,
    'image' => $staffMember->photo ?: null,
    'url' => route('staff.show', [$staffMember->id, str($staffMember->handle)->slug()]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@php
    // "Current team" mixes the staff member's own direct team affiliations
    // (staff_teams, no organization involved) with the linked player
    // profile's current teams, if any — same real person, both roles.
    $currentTeamEntries = $currentTeams->map(fn ($entry) => (object) [
        'id' => $entry->team_id,
        'name' => $entry->team_name,
        'logo' => $entry->team_logo,
        'role' => $entry->role,
        'joined_at' => $entry->joined_at,
        'source' => 'staff',
    ]);

    if ($staffMember->player) {
        $currentTeamEntries = $currentTeamEntries->concat(
            $staffMember->player->currentTeams->map(fn ($team) => (object) [
                'id' => $team->id,
                'name' => $team->name,
                'logo' => $team->logo,
                'role' => $team->pivot->role,
                'joined_at' => $team->pivot->joined_at,
                'source' => 'player',
            ])
        );
    }
@endphp

@section('content')
    @include('public.staff.header')

    <div class="grid grid-cols-12 gap-6">
        <aside class="col-span-12 lg:col-span-3 space-y-2">
            @include('public.news._sidebar', ['news' => $news, 'sectionTitle' => __('news.press_section')])
        </aside>

        <section class="col-span-12 lg:col-span-6 space-y-6">
            @if ($formerTeams->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('staff.old_teams') }}</span>
                        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                    </div>
                    <div class="space-y-2">
                        @foreach ($formerTeams as $entry)
                            <a href="{{ route('teams.show', [$entry->team_id, str($entry->team_name)->slug()]) }}" class="group block">
                                <div class="tournament-card flex bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                                    <div class="w-1 shrink-0 bg-gray-500"></div>
                                    <div class="flex items-center gap-4 p-3 min-w-0 flex-1">
                                        <div class="relative shrink-0">
                                            <img class="w-10 h-10 object-contain" src="{{ $entry->team_logo ?: asset('storage/images/default-team.webp') }}" alt="">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">{{ $entry->team_name }}</p>
                                            <div class="mt-1 flex items-center gap-2 min-w-0">
                                                <span class="shrink-0 text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm bg-gray-500/10 text-gray-400">
                                                    {{ \App\Helpers\StaffRoleLabel::label($entry->role, $staffMember->pronouns) }}
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
                </div>
            @endif

            @if ($formerOrganizations->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('staff.old_organizations') }}</span>
                        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                    </div>
                    <div class="space-y-2">
                        @foreach ($formerOrganizations as $entry)
                            <a href="{{ route('organizations.show', [$entry->organization_id, str($entry->organization_name)->slug()]) }}" class="group block">
                                <div class="tournament-card flex bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                                    <div class="w-1 shrink-0 bg-gray-500"></div>
                                    <div class="flex items-center gap-4 p-3 min-w-0 flex-1">
                                        <div class="relative shrink-0">
                                            <img class="w-10 h-10 object-contain" src="{{ $entry->organization_logo ?: asset('storage/images/default-team.webp') }}" alt="">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">{{ $entry->organization_name }}</p>
                                            <div class="mt-1 flex items-center gap-2 min-w-0">
                                                <span class="shrink-0 text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm bg-gray-500/10 text-gray-400">
                                                    {{ \App\Helpers\StaffRoleLabel::label($entry->role, $staffMember->pronouns) }}
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
                </div>
            @endif

        </section>

        <aside class="col-span-12 lg:col-span-3 space-y-6">
            @if ($currentTeamEntries->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('staff.teams') }}</span>
                        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                    </div>
                    <div class="space-y-2">
                        @foreach ($currentTeamEntries as $entry)
                            @php
                                $roleHelper = $entry->source === 'player' ? \App\Helpers\RosterRole::class : \App\Helpers\StaffRoleLabel::class;
                            @endphp
                            <a href="{{ route('teams.show', [$entry->id, str($entry->name)->slug()]) }}" class="group block">
                                <div class="tournament-card flex bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                                    <div class="w-1 shrink-0 {{ $roleHelper::barClass($entry->role) }}"></div>
                                    <div class="flex items-center gap-4 p-3 min-w-0 flex-1">
                                        <div class="relative shrink-0">
                                            <img class="w-10 h-10 object-contain" src="{{ $entry->logo ?: asset('storage/images/default-team.webp') }}" alt="">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">{{ $entry->name }}</p>
                                            <div class="mt-1 flex items-center gap-2 min-w-0">
                                                <span class="shrink-0 text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm {{ $roleHelper::badgeClass($entry->role) }}">
                                                    {{ $roleHelper::label($entry->role, $staffMember->pronouns) }}
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
                </div>
            @endif

            @if ($currentOrganizations->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('staff.organizations') }}</span>
                        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                    </div>
                    <div class="space-y-2">
                        @foreach ($currentOrganizations as $entry)
                            <a href="{{ route('organizations.show', [$entry->organization_id, str($entry->organization_name)->slug()]) }}" class="group block">
                                <div class="tournament-card flex bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm overflow-hidden hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                                    <div class="w-1 shrink-0 {{ \App\Helpers\StaffRoleLabel::barClass($entry->role) }}"></div>
                                    <div class="flex items-center gap-4 p-3 min-w-0 flex-1">
                                        <div class="relative shrink-0">
                                            <img class="w-10 h-10 object-contain" src="{{ $entry->organization_logo ?: asset('storage/images/default-team.webp') }}" alt="">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-black tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">{{ $entry->organization_name }}</p>
                                            <div class="mt-1 flex items-center gap-2 min-w-0">
                                                <span class="shrink-0 text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm {{ \App\Helpers\StaffRoleLabel::badgeClass($entry->role) }}">
                                                    {{ \App\Helpers\StaffRoleLabel::label($entry->role, $staffMember->pronouns) }}
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
                </div>
            @endif
        </aside>
    </div>
@endsection
