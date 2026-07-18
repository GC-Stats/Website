{{--
    GC-Stats — Team: edit profile

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('team.edit.title'))

@php $teamParams = [$team, $team->routeSlug()]; @endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <div class="border-b border-border-subtle pb-6">
                <a href="{{ route('teams.show', $team->id) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-4">
                    &larr; {{ $team->name }}
                </a>
                <div class="flex items-center justify-between">
                    <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('team.edit.title') }}</h1>
                    @can('team.roles.manage')
                        <a href="{{ route('teams.roles.index', $teamParams) }}"
                           class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('team.roles.title') }}
                        </a>
                    @endcan
                </div>
            </div>

            @if (session('status'))
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __('team.edit.status.'.session('status')) }}
                </div>
            @endif

            @can('team.logo.upload')
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.edit.logo.title') }}</h2>

                    <div class="flex items-center gap-4">
                        <img src="{{ $team->logo }}" alt="" class="w-16 h-16 object-contain border border-white/10 rounded-lg bg-black/40 p-2">

                        <form method="POST" action="{{ route('teams.logo.update', $teamParams) }}" enctype="multipart/form-data" class="flex-1 flex items-center gap-3">
                            @csrf
                            <input type="file" name="logo" accept="image/*" required
                                   class="flex-1 text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-sm file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-white/5 file:text-white hover:file:bg-white/10">
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 shrink-0">
                                {{ __('team.edit.logo.submit') }}
                            </button>
                        </form>
                    </div>
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endcan

            @can('team.profile.edit')
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.edit.profile.title') }}</h2>

                    <form method="POST" action="{{ route('teams.update', $teamParams) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @include('team._profile-form', ['team' => $team])

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('team.edit.profile.submit') }}
                        </button>
                    </form>
                </div>
            @endcan
        </section>
    </div>
@endsection
