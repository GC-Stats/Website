{{--
    GC-Stats — Admin: edit match

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@php
    $editLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'matches.edit');
    $importLocked = \App\Http\Controllers\Admin\MatchController::isLockedFor($tournament, $match, 'matches.import');
    $anyLocked = $editLocked || (auth()->user()->can('matches.import') && $importLocked);
@endphp

@section('title', __('admin.matches.edit.title'))

@section('content')
    <a href="{{ route('admin.matches.show', [$tournament, $match]) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }} vs {{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}
    </a>

    <div class="flex items-center justify-center gap-6 border-b border-white/10 pb-6 mb-6">
        <div class="flex flex-1 items-center justify-end gap-3 text-right">
            @if ($match->teamA)
                <img src="{{ $match->teamA->logo }}" alt="" class="h-12 w-12 object-contain">
            @endif
            <h2 class="text-lg font-black uppercase leading-none text-white">{{ \App\Support\MatchDisplay::teamName($match->teamA, $match->status) }}</h2>
        </div>
        <div class="text-xl font-black italic text-gray-500">VS</div>
        <div class="flex flex-1 items-center gap-3">
            <h2 class="text-lg font-black uppercase leading-none text-white">{{ \App\Support\MatchDisplay::teamName($match->teamB, $match->status) }}</h2>
            @if ($match->teamB)
                <img src="{{ $match->teamB->logo }}" alt="" class="h-12 w-12 object-contain">
            @endif
        </div>
    </div>

    @if ($anyLocked)
        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.matches.finished_locked') }}
        </div>
    @endif

    @if (! empty($importResults))
        <div class="mb-6 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4 space-y-1">
            <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-2">{{ __('admin.matches.import.results') }}</p>
            @foreach ($importResults as $result)
                <p class="text-xs {{ $result['status'] === 'success' ? 'text-green-400' : ($result['status'] === 'error' ? 'text-red-400' : 'text-gray-400') }}">
                    {{ $result['map'] }} — {{ $result['message'] }}
                </p>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
            <fieldset @disabled($editLocked)>
                <form method="POST" action="{{ route('admin.matches.update', [$tournament, $match]) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('admin.matches._form', ['match' => $match, 'phases' => $phases, 'teams' => $teams, 'sticky' => []])

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.matches.edit.submit') }}
                    </button>
                </form>
            </fieldset>
        </div>

        @can('matches.import')
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.matches.import.title') }}</h2>
                <p class="text-xs text-gray-500">{{ __('admin.matches.import.help') }}</p>
                <fieldset @disabled($importLocked)>
                    <form method="POST" action="{{ route('admin.matches.import-wikicode', [$tournament, $match]) }}" class="space-y-3">
                        @csrf
                        <textarea name="wikicode" rows="8" placeholder="{{ __('admin.matches.import.placeholder') }}"
                                  class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-gc-yellow transition">{{ old('wikicode') }}</textarea>
                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                            {{ __('admin.matches.import.submit') }}
                        </button>
                    </form>
                </fieldset>
            </div>
        @endcan
    </div>
@endsection
