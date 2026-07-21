{{--
    GC-Stats — Admin: tournament operations (bulk/utility actions)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@php
    $finished = $tournament->status === 'finished';
    $patchLocked = $finished && ! auth()->user()->can('operations.patch.finished');
    $bulkCreateLocked = $finished && ! auth()->user()->can('operations.bulk-create.finished');
    $cachePurgeLocked = $finished && ! auth()->user()->can('operations.cache-purge.finished');
@endphp

@section('title', __('admin.operations.title').' — '.$tournament->name)

@section('content')
    <a href="{{ route('admin.tournaments.show', $tournament) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $tournament->name }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ __('admin.operations.title') }}</h1>

    {{-- admin.layout already renders session('status') globally --}}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @can('operations.patch')
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-1.5">{{ __('admin.operations.patch.title') }}</h2>
                <p class="text-xs text-gray-500 mb-4">{{ __('admin.operations.patch.help') }}</p>

                @if ($patchLocked)
                    <div class="mb-4 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-xs rounded-lg px-4 py-3">
                        {{ __('admin.matches.finished_locked') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.tournaments.operations.patch', $tournament) }}" x-data="{ patch: '' }">
                    @csrf
                    <fieldset @disabled($patchLocked) class="space-y-4">
                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.operations.patch.field') }}</span>
                            <input type="text" name="patch" x-model="patch" required placeholder="9.10"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </label>

                        <div class="border-t border-white/10 pt-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3">{{ __('admin.operations.patch.scope') }}</p>

                            <label class="block mb-3">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.phase') }}</span>
                                <select name="phase_id"
                                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                    <option value="">{{ __('admin.matches.all_phases') }}</option>
                                    @foreach ($phases as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.operations.patch.date_from') }}</span>
                                    <input type="date" name="date_from"
                                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                </label>
                                <label class="block">
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.operations.patch.date_to') }}</span>
                                    <input type="date" name="date_to"
                                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                </label>
                            </div>
                        </div>

                        <x-confirm-modal
                            :title="__('admin.operations.patch.title')"
                            :body="__('admin.operations.patch.confirm_body')"
                            :trigger-label="__('admin.operations.patch.submit')"
                            :submit-label="__('admin.operations.patch.submit')"
                            trigger-class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] disabled:opacity-40"
                            submit-class="bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]"
                        />
                    </fieldset>
                </form>
            </div>
        @endcan

        @can('operations.bulk-create')
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-1.5">{{ __('admin.operations.bulk_create.title') }}</h2>
                <p class="text-xs text-gray-500 mb-4">{{ __('admin.operations.bulk_create.help') }}</p>

                @if ($bulkCreateLocked)
                    <div class="mb-4 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-xs rounded-lg px-4 py-3">
                        {{ __('admin.matches.finished_locked') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.tournaments.operations.bulk-create', $tournament) }}">
                    @csrf
                    <fieldset @disabled($bulkCreateLocked) class="space-y-4">
                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.phase') }}</span>
                            <select name="phase_id" required
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                @foreach ($phases as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.operations.bulk_create.count') }}</span>
                                <input type="number" name="count" value="8" min="1" max="100" required
                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                            </label>
                            <label class="block">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.best_of') }}</span>
                                <select name="best_of"
                                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                    @foreach ([1, 2, 3, 4, 5] as $f)
                                        <option value="{{ $f }}" @selected($f == 3)>BO{{ $f }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <x-timezone-select name="scheduled_at" :label="__('admin.matches.scheduled_at')" :required="true" />

                        <x-confirm-modal
                            :title="__('admin.operations.bulk_create.title')"
                            :body="__('admin.operations.bulk_create.confirm_body')"
                            :trigger-label="__('admin.operations.bulk_create.submit')"
                            :submit-label="__('admin.operations.bulk_create.submit')"
                            trigger-class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] disabled:opacity-40"
                            submit-class="bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]"
                        />
                    </fieldset>
                </form>
            </div>
        @endcan

        @can('operations.cache-purge')
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl lg:col-span-2">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-1.5">{{ __('admin.operations.cache_purge.title') }}</h2>
                <p class="text-xs text-gray-500 mb-4">{{ __('admin.operations.cache_purge.help') }}</p>

                @if ($cachePurgeLocked)
                    <div class="mb-4 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-xs rounded-lg px-4 py-3">
                        {{ __('admin.matches.finished_locked') }}
                    </div>
                @endif

                @if (session('purgeResult'))
                    @php $result = session('purgeResult'); @endphp
                    <div class="mb-4 rounded-lg px-4 py-3 text-sm {{ $result['renewed'] ? 'bg-green-500/10 border border-green-500/30 text-green-400' : ($result['preserved'] ? 'bg-yellow-500/10 border border-yellow-500/30 text-yellow-400' : 'bg-red-500/10 border border-red-500/30 text-red-400') }}">
                        <span class="font-black uppercase tracking-widest text-xs">{{ $result['status'] ?? __('admin.operations.cache_purge.no_response') }}</span>
                        <span class="block mt-1">
                            @if ($result['renewed'])
                                {{ __('admin.operations.cache_purge.result_renewed') }}
                            @elseif ($result['preserved'])
                                {{ __('admin.operations.cache_purge.result_failed_preserved') }}
                            @else
                                {{ __('admin.operations.cache_purge.result_failed_missing') }}
                            @endif
                        </span>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.tournaments.operations.cache-purge', $tournament) }}">
                    @csrf
                    <fieldset @disabled($cachePurgeLocked) class="grid grid-cols-1 md:grid-cols-[160px_1fr_auto] gap-3 items-end">
                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.operations.cache_purge.region') }}</span>
                            <select name="region" required
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                @foreach ($riotRegions as $region)
                                    <option value="{{ $region }}">{{ $region }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.matches.maps.api_match_id') }}</span>
                            <input type="text" name="api_match_id" required
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white font-mono focus:outline-none focus:border-gc-yellow transition">
                        </label>

                        <x-confirm-modal
                            :title="__('admin.operations.cache_purge.title')"
                            :body="__('admin.operations.cache_purge.confirm_body')"
                            :trigger-label="__('admin.operations.cache_purge.submit')"
                            :submit-label="__('admin.operations.cache_purge.submit')"
                            trigger-class="font-bold uppercase text-xs tracking-widest px-6 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] disabled:opacity-40"
                            submit-class="bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]"
                        />
                    </fieldset>
                </form>
            </div>
        @endcan
    </div>
@endsection
