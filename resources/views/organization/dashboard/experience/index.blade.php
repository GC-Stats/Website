{{--
    GC-Stats — Organization dashboard: experience (XP)

    Browsable list of every currently active tournament — click into one to
    declare/edit this organization's XP entries for it (or drill into one of
    its matches). See Organization\ExperienceController::index().

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', $organization->name.' — '.__('admin.staff_experience.title'))

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <h1 class="text-xl font-black uppercase tracking-tighter text-white">{{ __('admin.staff_experience.title') }}</h1>

        <form method="GET" action="{{ route('organization-dashboard.experience.index', $organization) }}" class="flex flex-wrap gap-2 flex-1 min-w-[200px] justify-end">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.tournaments.search_placeholder') }}"
                   class="flex-1 max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

            <x-styled-select name="region" :selected="$region" autosubmit
                :options="collect(['' => __('admin.tournaments.all_regions')])->merge(collect($regions)->mapWithKeys(fn ($r) => [$r => $r]))" />

            <x-styled-select name="status" :selected="$status" autosubmit
                :options="collect(['' => __('admin.tournaments.all_statuses')])->merge(collect(['upcoming', 'live', 'finished'])->mapWithKeys(fn ($s) => [$s => __('admin.tournaments.status.'.$s)]))" />

            <x-styled-select name="sort" :selected="$sort" autosubmit
                :options="[
                    'start_date' => __('admin.tournaments.sort.start_date'),
                    'name' => __('admin.tournaments.sort.name'),
                ]" />

            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.tournaments.search_submit') }}
            </button>
        </form>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3"></th>
                    <th class="px-4 py-3">{{ __('admin.tournaments.name') }}</th>
                    <th class="px-4 py-3">{{ __('admin.tournaments.region') }}</th>
                    <th class="px-4 py-3">{{ __('admin.tournaments.status_column') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tournaments as $tournament)
                    <tr class="border-b border-b-white/10 last:border-b-0">
                        <td class="px-4 py-3">
                            <img src="{{ $tournament->logo }}" alt="" class="w-8 h-8 rounded-lg object-cover bg-black/30">
                        </td>
                        <td class="px-4 py-3 text-white font-semibold">{{ $tournament->name }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $tournament->region }}</td>
                        <td class="px-4 py-3">
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg {{ $tournament->status === 'finished' ? 'bg-white/5 text-gray-400' : ($tournament->status === 'live' ? 'bg-red-500/10 text-red-400' : 'bg-green-500/10 text-green-400') }}">
                                {{ __('admin.tournaments.status.'.$tournament->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('organization-dashboard.experience.tournaments.show', [$organization, $tournament]) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.tournaments.manage') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.staff_experience.active_tournaments_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $tournaments->links() }}
@endsection
