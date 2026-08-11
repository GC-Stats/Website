{{--
    GC-Stats — Admin: edit tournament

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $tournament->name)

@section('content')
    <a href="{{ route('admin.tournaments.show', $tournament) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $tournament->name }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ __('admin.tournaments.edit.title') }}</h1>

    @php
        $finishedLocked = $tournament->status === 'finished' && ! auth()->user()->can('tournaments.finished.edit');
        $inactiveLocked = ! $tournament->active && ! auth()->user()->can('tournaments.inactive.edit');
    @endphp

    @if ($finishedLocked)
        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.tournaments.finished_locked') }}
        </div>
    @endif

    @if ($inactiveLocked)
        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.tournaments.inactive_locked') }}
        </div>
    @endif

    <fieldset @disabled($finishedLocked || $inactiveLocked)>
        <form method="POST" action="{{ route('admin.tournaments.update', $tournament) }}">
            @csrf
            @method('PUT')

            @include('admin.tournaments._form', ['tournament' => $tournament, 'regions' => $regions, 'categories' => $categories])

            <button type="submit"
                    class="mt-6 w-full md:w-auto font-bold uppercase text-xs tracking-widest px-8 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                {{ __('admin.tournaments.edit.submit') }}
            </button>
        </form>
    </fieldset>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4 mt-6">
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('tournament.edit.logo.title') }}</h2>

        <x-admin.logo-upload-form
            :current-url="$tournament->logo"
            :action-url="route('admin.tournaments.logo.update', $tournament)"
            :submit-label="__('tournament.edit.logo.submit')"
            :themeable="true"
            :theme-universal-label="__('tournament.edit.logo.theme_universal')"
            :theme-dark-label="__('tournament.edit.logo.theme_dark')"
            :theme-light-label="__('tournament.edit.logo.theme_light')"
        />
        @error('logo')
        <p class="text-xs text-red-400">{{ $message }}</p>
        @enderror

        <x-admin.logo-history
            :logos="$tournament->logos()->orderByDesc('from')->get()"
            folder="tournaments"
            :add-url="route('admin.tournaments.logo.history.store', $tournament)"
            :update-url="fn ($logo) => route('admin.tournaments.logo.history.update', [$tournament, $logo->id])"
            :delete-url="fn ($logo) => route('admin.tournaments.logo.history.destroy', [$tournament, $logo->id])"
            :title="__('tournament.edit.logo.history_title')"
            :from-label="__('tournament.edit.logo.history_from')"
            :until-label="__('tournament.edit.logo.history_until')"
            :visible-label="__('tournament.edit.logo.history_visible')"
            :save-label="__('team.roster.save')"
            :add-label="__('tournament.edit.logo.history_add')"
            :remove-label="__('team.roster.remove')"
            :remove-confirm-title="__('team.roster.remove')"
            :remove-confirm-body="fn ($logo) => __('tournament.edit.logo.history_remove_confirm')"
            :empty-label="__('tournament.edit.logo.history_empty')"
            :themeable="true"
            :theme-universal-label="__('tournament.edit.logo.theme_universal')"
            :theme-dark-label="__('tournament.edit.logo.theme_dark')"
            :theme-light-label="__('tournament.edit.logo.theme_light')"
        />
    </div>
@endsection
