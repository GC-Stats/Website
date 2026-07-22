{{--
    GC-Stats — Admin: tournaments list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.tournaments.title'))

@section('content')
    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <form method="GET" action="{{ route('admin.tournaments.index') }}" class="flex flex-wrap gap-2 flex-1 min-w-[200px]">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.tournaments.search_placeholder') }}"
                   class="flex-1 max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

            <select name="region" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.tournaments.all_regions') }}</option>
                @foreach ($regions as $r)
                    <option value="{{ $r }}" @selected($region === $r)>{{ $r }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.tournaments.all_statuses') }}</option>
                @foreach (['upcoming', 'live', 'finished'] as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ __('admin.tournaments.status.'.$s) }}</option>
                @endforeach
            </select>

            <select name="active" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.tournaments.all_active') }}</option>
                <option value="1" @selected($active === '1')>{{ __('admin.tournaments.active') }}</option>
                <option value="0" @selected($active === '0')>{{ __('admin.tournaments.inactive') }}</option>
            </select>

            <select name="category" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.tournaments.all_categories') }}</option>
                @foreach ($categories as $c)
                    <option value="{{ $c }}" @selected($category === $c)>{{ $c }}</option>
                @endforeach
                <option value="__custom__" @selected($category === '__custom__')>{{ __('admin.tournaments.category_custom') }}</option>
            </select>

            <select name="sort" onchange="this.form.submit()"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="start_date" @selected($sort === 'start_date')>{{ __('admin.tournaments.sort.start_date') }}</option>
                <option value="name" @selected($sort === 'name')>{{ __('admin.tournaments.sort.name') }}</option>
            </select>

            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.tournaments.search_submit') }}
            </button>
        </form>

        @can('tournaments.create')
            <a href="{{ route('admin.tournaments.create') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                {{ __('admin.tournaments.create.title') }}
            </a>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3"></th>
                    @foreach ([['name', 'admin.tournaments.name'], ['region', 'admin.tournaments.region'], ['status', 'admin.tournaments.status_column'], ['teams_count', 'admin.tournaments.teams_count']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tournaments as $tournament)
                    <tr class="border-b border-b-white/10 last:border-b-0 border-l-2 {{ $tournament->active ? 'border-l-green-500/60' : 'border-l-red-500/60' }}">
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
                        <td class="px-4 py-3 text-gray-400">{{ $tournament->teams_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tournaments.show', $tournament) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.tournaments.manage') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $tournaments->links() }}
@endsection
