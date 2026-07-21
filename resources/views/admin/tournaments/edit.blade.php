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

    @if ($tournament->status === 'finished' && ! auth()->user()->can('tournaments.finished.edit'))
        <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 text-sm rounded-lg px-4 py-3">
            {{ __('admin.tournaments.finished_locked') }}
        </div>
    @endif

    <fieldset @disabled($tournament->status === 'finished' && ! auth()->user()->can('tournaments.finished.edit'))>
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
@endsection
