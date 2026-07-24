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

                    <x-logo-upload-form
                        :current-url="$team->logo"
                        :action-url="route('teams.logo.update', $teamParams)"
                        :submit-label="__('team.edit.logo.submit')"
                    />
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    <x-logo-history
                        :logos="$team->logos()->orderByDesc('from')->get()"
                        folder="teams"
                        :add-url="route('teams.logo.history.store', $teamParams)"
                        :update-url="fn ($logo) => route('teams.logo.history.update', [...$teamParams, $logo->id])"
                        :delete-url="fn ($logo) => route('teams.logo.history.destroy', [...$teamParams, $logo->id])"
                        :title="__('team.edit.logo.history_title')"
                        :from-label="__('team.edit.logo.history_from')"
                        :until-label="__('team.edit.logo.history_until')"
                        :save-label="__('team.roster.save')"
                        :add-label="__('team.edit.logo.history_add')"
                        :remove-label="__('team.roster.remove')"
                        :remove-confirm-title="__('team.roster.remove')"
                        :remove-confirm-body="fn ($logo) => __('team.edit.logo.history_remove_confirm')"
                        :empty-label="__('team.edit.logo.history_empty')"
                    />
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

            @can('team.tags.manage')
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.edit.tags.title') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('team.edit.tags.body') }}</p>

                    <form method="POST" action="{{ route('teams.tags.update', $teamParams) }}" class="space-y-3"
                          x-data="{ tags: @js(old('tags', $team->fanTags()) ?: ['']) }">
                        @csrf
                        @method('PUT')

                        <template x-for="(tag, index) in tags" :key="index">
                            <div class="flex items-center gap-2">
                                <input type="text" :name="'tags[' + index + ']'" x-model="tags[index]"
                                       placeholder="{{ __('team.edit.tags.placeholder') }}"
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                <button type="button" @click="tags.splice(index, 1)"
                                        class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-3 py-2.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                                    {{ __('team.edit.tags.remove') }}
                                </button>
                            </div>
                        </template>

                        <button type="button" @click="tags.push('')"
                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('team.edit.tags.add') }}
                        </button>

                        @error('tags')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('team.edit.tags.submit') }}
                        </button>
                    </form>
                </div>
            @endcan

            @can('team.roster.manage')
                <x-roster-panel
                    :current="$roster"
                    :history="$rosterHistory"
                    :add-url="route('teams.roster.store', $teamParams)"
                    :sync-url="route('teams.roster.sync', $teamParams)"
                    :roles="__('team.roster.roles')"
                    :title="__('team.roster.title')"
                    :history-title="__('team.roster.history_title')"
                    :add-label="__('team.roster.add')"
                    :role-label="__('team.roster.role')"
                    :joined-at-label="__('team.roster.joined_at')"
                    :left-at-label="__('team.roster.left_at')"
                    :save-label="__('team.roster.save')"
                    :assign-label="__('team.roster.assign')"
                    :remove-label="__('team.roster.remove')"
                    :remove-confirm-body="fn ($entry) => __('team.roster.remove_confirm', ['player' => $entry->player_handle])"
                    :current-empty-label="__('team.roster.current_empty')"
                    :history-empty-label="__('team.roster.history_empty')"
                    heading-tag="h2"
                />
            @endcan
        </section>
    </div>
@endsection
