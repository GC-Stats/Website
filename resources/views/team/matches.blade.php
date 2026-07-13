{{--
    GC-Stats — Team matches page

    Lists all matches played by the team, with pagination and filters.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('team.title.matches', ["team" => $team['name']]))

@section('content')
    @include("team.header")

    <div class="max-w-6xl mx-auto space-y-4">
        <section class="col-span-12 lg:col-span-6 space-y-4">
            @forelse($matches as $match)
                <x-match :match="$match" />
            @empty
                <h3 class="text-center text-gray-400">{{ __('team.empty.matches_history') }}</h3>
            @endforelse
        </section>
    </div>

    <div class="mt-8 mb-12">
        {{ $matches->links() }}
    </div>
@endsection
