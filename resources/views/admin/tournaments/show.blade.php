{{--
    GC-Stats — Admin: tournament detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@php
    $regionColors = config('regions.colors', []);
    // Team add/create are plain POST redirects (full page reload), so Alpine's
    // modal state resets. On success we want it closed (the updated team list
    // is visible right behind it) — only reopen for an in-progress search or a
    // failed add, so the visitor doesn't lose their search/input and sees why
    // it failed.
    $teamModalOpen = $search !== ''
        || session('error') === 'tournament-team-already-registered'
        || $errors->any();
@endphp

@section('title', $tournament->name)

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <a href="{{ route('admin.tournaments.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition">
            &larr; {{ __('admin.tournaments.title') }}
        </a>

        <a href="{{ route('tournaments.show', [$tournament->id, str($tournament->name)->slug()]) }}" target="_blank" rel="noopener"
           class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
            {{ __('admin.tournaments.public_page') }}
        </a>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4 shadow-xl mb-6">
        <div class="flex flex-col md:flex-row gap-4 md:items-center">
            <div class="flex h-28 w-28 shrink-0 items-center justify-center self-center md:self-start rounded-lg bg-black/30">
                <img src="{{ $tournament->logo }}" alt="" class="max-h-full max-w-full object-contain">
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-white/5 text-gray-300">{{ $tournament->category }}</span>
                    <span class="text-[10px] font-black uppercase tracking-widest" style="color: {{ $regionColors[$tournament->region] ?? '#888' }}">{{ $tournament->region }}</span>
                    <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg {{ $tournament->status === 'finished' ? 'bg-white/5 text-gray-400' : ($tournament->status === 'live' ? 'bg-red-500/10 text-red-400' : 'bg-green-500/10 text-green-400') }}">
                        {{ __('admin.tournaments.status.'.$tournament->status) }}
                    </span>
                    @can('tournaments.activate')
                        <form method="POST" action="{{ route('admin.tournaments.toggle-active', $tournament) }}"
                              onsubmit="return confirm('{{ $tournament->active ? __('admin.tournaments.deactivate_confirm') : __('admin.tournaments.activate_confirm') }}');">
                            @csrf
                            @method('PATCH')
                            @if ($tournament->active)
                                <button type="submit"
                                        class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 transition">
                                    {{ __('admin.tournaments.active') }}
                                </button>
                            @else
                                <button type="submit"
                                        class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition">
                                    {{ __('admin.tournaments.inactive') }}
                                </button>
                            @endif
                        </form>
                    @else
                        @if ($tournament->active)
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-green-500/10 text-green-400">{{ __('admin.tournaments.active') }}</span>
                        @else
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-red-500/10 text-red-400">{{ __('admin.tournaments.inactive') }}</span>
                        @endif
                    @endcan
                </div>

                <h1 class="text-2xl font-black uppercase italic tracking-tighter text-white mb-2 truncate">{{ $tournament->name }}</h1>

                <div class="flex flex-wrap items-center gap-3 text-xs font-bold uppercase tracking-tight text-gray-400">
                    <span class="flex items-center gap-1.5">
                        @svg('fas-calendar-days', 'w-3 h-3', ['aria-hidden' => 'true'])
                        {{ $tournament->start_date?->format('d M Y') }} - {{ $tournament->end_date?->format('d M Y') }}
                    </span>
                    @if ($tournament->location)
                        <span class="flex items-center gap-1.5">
                            @svg('fas-location-dot', 'w-3 h-3', ['aria-hidden' => 'true'])
                            {{ $tournament->location }}
                        </span>
                    @endif
                    @if ($tournament->prize_pool)
                        <span class="flex items-center gap-1.5 text-gc-yellow">
                            @svg('fas-coins', 'w-3 h-3', ['aria-hidden' => 'true'])
                            {{ $tournament->prize_pool }}
                        </span>
                    @endif
                </div>

                @if ($tournament->description)
                    <div class="mt-4 border-l-2 border-l-gc-yellow bg-white/5 rounded-lg p-3 text-xs text-gray-300">
                        {{ $tournament->description }}
                    </div>
                @endif
            </div>

            <div class="flex flex-row md:flex-col gap-2 shrink-0 md:w-48">
                @can('tournaments.edit')
                    <a href="{{ route('admin.tournaments.edit', $tournament) }}"
                       class="flex-1 text-center font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        {{ __('admin.tournaments.edit.title') }}
                    </a>
                @endcan
                @can('matches.view')
                    <a href="{{ route('admin.matches.index', $tournament) }}"
                       class="flex-1 text-center font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.matches.title') }}
                    </a>
                @endcan
                @canany(['operations.patch', 'operations.bulk-create', 'operations.cache-purge'])
                    <a href="{{ route('admin.tournaments.operations.index', $tournament) }}"
                       class="flex-1 text-center font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                        {{ __('admin.operations.title') }}
                    </a>
                @endcanany
                @can('tournaments.delete')
                    <form method="POST" action="{{ route('admin.tournaments.destroy', $tournament) }}" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <x-confirm-modal
                            :title="__('admin.tournaments.delete.title')"
                            :body="__('admin.tournaments.delete.confirm_body', ['tournament' => $tournament->name])"
                            :trigger-label="__('admin.tournaments.delete.trigger')"
                            :submit-label="__('admin.tournaments.delete.trigger')"
                            trigger-class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                        />
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow flex items-center gap-2">
                    @svg('fas-diagram-project', 'w-3.5 h-3.5', ['aria-hidden' => 'true'])
                    {{ __('admin.tournaments.phases.title') }}
                </h2>
                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-white/5 text-gray-400">
                    {{ $tournament->rootPhases->count() }}
                </span>
            </div>

            @forelse ($tournament->rootPhases as $phase)
                <div class="relative pl-5 pb-5 last:pb-0 border-l border-white/10 last:border-transparent">
                    <span class="absolute -left-[3.5px] top-1 w-[7px] h-[7px] rounded-full bg-gc-yellow"></span>

                    <h3 class="text-sm font-black uppercase tracking-tight text-white">{{ $phase->name }}</h3>

                    @if ($phase->start_date || $phase->end_date)
                        <p class="text-[10px] font-semibold text-gray-500 mt-0.5">
                            @if ($phase->start_date && $phase->end_date)
                                {{ $phase->start_date->format('d M Y') }} &ndash; {{ $phase->end_date->format('d M Y') }}
                            @elseif ($phase->start_date)
                                {{ $phase->start_date->format('d M Y') }}
                            @else
                                {{ $phase->end_date->format('d M Y') }}
                            @endif
                        </p>
                    @endif

                    @if ($phase->children->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                            @foreach ($phase->children as $child)
                                @include('admin.tournaments._phase-node', ['phase' => $child, 'tournament' => $tournament])
                            @endforeach
                        </div>
                    @elseif ($phase->format)
                        <p class="text-xs font-bold uppercase text-gray-500 mt-1">{{ $phase->format }}</p>
                        @include('admin.tournaments._phase-qualifications', ['phase' => $phase, 'tournament' => $tournament])
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 italic text-center py-10">{{ __('admin.tournaments.phases.empty') }}</p>
            @endforelse
        </div>

        <div class="lg:col-span-5 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">
                    {{ __('admin.tournaments.teams.title') }} ({{ $teams->total() }})
                </h2>

                @can('tournaments.teams.manage')
                    <x-modal :title="__('admin.tournaments.teams.add')" :open-by-default="$teamModalOpen">
                        <x-slot:trigger>
                            <button type="button"
                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                + {{ __('admin.tournaments.teams.add') }}
                            </button>
                        </x-slot:trigger>

                        <div x-data="{ mode: '{{ $errors->has('name') ? 'create' : 'search' }}' }">
                            <div class="flex gap-2 mb-4">
                                <button type="button" @click="mode = 'search'"
                                        class="flex-1 font-bold uppercase text-[10px] tracking-widest px-3 py-2 rounded-lg transition"
                                        :class="mode === 'search' ? 'bg-gc-yellow text-black' : 'bg-white/5 border border-white/10 text-white hover:bg-white/10'">
                                    {{ __('admin.tournaments.teams.mode_search') }}
                                </button>
                                <button type="button" @click="mode = 'create'"
                                        class="flex-1 font-bold uppercase text-[10px] tracking-widest px-3 py-2 rounded-lg transition"
                                        :class="mode === 'create' ? 'bg-gc-yellow text-black' : 'bg-white/5 border border-white/10 text-white hover:bg-white/10'">
                                    {{ __('admin.tournaments.teams.mode_create') }}
                                </button>
                            </div>

                            <div x-show="mode === 'search'" class="space-y-3">
                                <form method="GET" action="{{ route('admin.tournaments.show', $tournament) }}" class="flex gap-2">
                                    <input type="text" name="q" x-ref="search" value="{{ $search }}" placeholder="{{ __('admin.tournaments.teams.search_placeholder') }}"
                                           class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                    <button type="submit"
                                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                        {{ __('admin.tournaments.teams.search_submit') }}
                                    </button>
                                </form>

                                @if ($search)
                                    <div class="space-y-2">
                                        @forelse ($searchResults as $found)
                                            <form method="POST" action="{{ route('admin.tournaments.teams.store', $tournament) }}" class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                                @csrf
                                                <input type="hidden" name="team_id" value="{{ $found->id }}">
                                                <p class="text-xs text-white font-semibold">{{ $found->name }}</p>
                                                <button type="submit"
                                                        class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                                    {{ __('admin.tournaments.teams.attach') }}
                                                </button>
                                            </form>
                                        @empty
                                            <p class="text-xs text-gray-500">{{ __('admin.tournaments.teams.search_empty') }}</p>
                                        @endforelse
                                    </div>
                                @endif
                            </div>

                            <form x-show="mode === 'create'" method="POST" action="{{ route('admin.tournaments.teams.quick-create', $tournament) }}" class="space-y-3">
                                @csrf
                                <label class="block">
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.tournaments.teams.create_name_label') }}</span>
                                    <input type="text" name="name" required
                                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                </label>
                                <button type="submit"
                                        class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                    {{ __('admin.tournaments.teams.create_submit') }}
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endcan
            </div>

            <div class="space-y-2">
                @forelse ($teams as $team)
                    <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <img src="{{ $team->logo }}" alt="" class="w-6 h-6 object-cover rounded-lg shrink-0">
                            <p class="text-sm text-white font-semibold truncate">{{ $team->name }}</p>
                        </div>
                        @can('tournaments.teams.manage')
                            <form method="POST" action="{{ route('admin.tournaments.teams.destroy', [$tournament, $team]) }}">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="__('admin.tournaments.teams.remove')"
                                    :body="__('admin.tournaments.teams.remove_confirm', ['team' => $team->name])"
                                    :trigger-label="__('admin.tournaments.teams.remove')"
                                    :submit-label="__('admin.tournaments.teams.remove')"
                                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic text-center py-10">{{ __('admin.tournaments.teams.empty') }}</p>
                @endforelse
            </div>

            {{ $teams->links() }}
        </div>
    </div>

    @if ($search !== '')
        {{-- The team search form is a plain GET submit, so ?q= lingers in the address bar
             after the modal opens to show results. Strip it once the page has loaded (not
             on submit) so a later F5 lands on a clean URL instead of reopening the modal. --}}
        @push('scripts')
            <script>
                (function () {
                    const url = new URL(window.location.href);
                    if (url.searchParams.has('q')) {
                        url.searchParams.delete('q');
                        window.history.replaceState({}, '', url);
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
