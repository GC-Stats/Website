{{--
    GC-Stats — Admin: edit emote

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $emote->name)

@section('content')
    <a href="{{ route('admin.emotes.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.emotes.title') }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ $emote->name }}</h1>

    <form method="POST" action="{{ route('admin.emotes.update', $emote) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('admin.emotes._form', ['emote' => $emote, 'teams' => $teams, 'sources' => $sources])

        <button type="submit"
                class="mt-6 w-full md:w-auto font-bold uppercase text-xs tracking-widest px-8 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            {{ __('admin.emotes.edit.submit') }}
        </button>
    </form>
@endsection
