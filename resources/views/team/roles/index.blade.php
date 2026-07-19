{{--
    GC-Stats — Team: roles list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('team.roles.title'))

@php $teamParams = [$team, $team->routeSlug()]; @endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <div class="border-b border-border-subtle pb-6">
                <a href="{{ route('teams.show', $team->id) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-4">
                    &larr; {{ __('team.roles.back_to_team', ['team' => $team->name]) }}
                </a>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('team.roles.title') }}</h1>
                        <p class="text-sm text-gray-400 mt-1">{{ $team->name }}</p>
                    </div>
                    <a href="{{ route('teams.edit', $teamParams) }}"
                       class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                        {{ __('team.edit.title') }}
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __('team.roles.status.'.session('status')) }}
                </div>
            @endif
            @error('role')
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3">{{ $message }}</div>
            @enderror

            <div class="flex items-center justify-end">
                <x-modal :title="__('team.roles.new_role.title')">
                    <x-slot:trigger>
                        <button type="button"
                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('team.roles.new_role.title') }}
                        </button>
                    </x-slot:trigger>

                    <form method="POST" action="{{ route('teams.roles.store', $teamParams) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('team.roles.new_role.name_label') }}
                            </label>
                            <input type="text" name="name" required pattern="[A-Za-z0-9_\-]+"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('name')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('team.roles.new_role.submit') }}
                        </button>
                    </form>
                </x-modal>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($roles as $role)
                    <a href="{{ route('teams.roles.show', [...$teamParams, $role]) }}"
                       class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl hover:border-gc-yellow/50 transition-all group">
                        <h2 class="text-sm font-black uppercase tracking-widest text-white group-hover:text-gc-yellow transition-colors mb-2">{{ $role->name }}</h2>
                        <p class="text-xs text-gray-500">{{ trans_choice('team.roles.member_count', $role->users_count, ['count' => $role->users_count]) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
