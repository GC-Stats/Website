{{--
    GC-Stats — Admin: teams list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.teams.title'))

@section('content')
    <form method="GET" action="{{ route('admin.teams.index') }}" class="flex flex-wrap gap-2 mb-6">
        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.teams.search_placeholder') }}"
               class="flex-1 max-w-sm bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

        <select name="sort" onchange="this.form.submit()"
                class="bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <option value="name" @selected($sort === 'name')>{{ __('admin.teams.sort.name') }}</option>
            <option value="country" @selected($sort === 'country')>{{ __('admin.teams.sort.country') }}</option>
            <option value="recent_activity" @selected($sort === 'recent_activity')>{{ __('admin.teams.sort.recent_activity') }}</option>
        </select>

        <select name="active_within" onchange="this.form.submit()"
                class="bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <option value="" @selected($activeWithin === '')>{{ __('admin.teams.active_within.any') }}</option>
            <option value="30d" @selected($activeWithin === '30d')>{{ __('admin.teams.active_within.30d') }}</option>
            <option value="90d" @selected($activeWithin === '90d')>{{ __('admin.teams.active_within.90d') }}</option>
            <option value="6m" @selected($activeWithin === '6m')>{{ __('admin.teams.active_within.6m') }}</option>
            <option value="1y" @selected($activeWithin === '1y')>{{ __('admin.teams.active_within.1y') }}</option>
        </select>

        <button type="submit"
                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
            {{ __('admin.teams.search_submit') }}
        </button>
    </form>

    <div class="bg-bg-card border border-border-subtle rounded-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-border-subtle text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.teams.title') }}</th>
                    <th class="px-4 py-3"></th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teams as $team)
                    <tr class="border-b border-border-subtle last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $team->name }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('teams.show', [$team, $team->routeSlug()]) }}" target="_blank" rel="noopener"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                {{ __('admin.teams.public_page') }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.teams.show', $team) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                {{ __('admin.teams.manage') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-xs">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $teams->links() }}
@endsection
