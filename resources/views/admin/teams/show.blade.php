{{--
    GC-Stats — Admin: team detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $team->name)

@section('content')
    <a href="{{ route('admin.teams.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.teams.title') }}
    </a>

    @php $teamParams = [$team, $team->routeSlug()]; @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $team->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('teams.show', $teamParams) }}" target="_blank" rel="noopener"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.teams.public_page') }}
            </a>
            <a href="{{ route('teams.roles.index', $teamParams) }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.nav.roles') }} &rarr;
            </a>
            @can('teams.merge')
                <a href="{{ route('admin.teams.merge.show', $team) }}"
                   class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                    {{ __('admin.teams.merge.trigger') }}
                </a>
            @endcan
            @can('teams.delete')
                <form method="POST" action="{{ route('admin.teams.destroy', $team) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="__('admin.teams.delete.title')"
                        :body="__('admin.teams.delete.confirm_body', ['team' => $team->name])"
                        :trigger-label="__('admin.teams.delete.trigger')"
                        :submit-label="__('admin.teams.delete.trigger')"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @can('teams.edit')
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.edit.logo.title') }}</h2>

                    <x-logo-upload-form
                        :current-url="$team->logo"
                        :action-url="route('admin.teams.logo.update', $team)"
                        :submit-label="__('team.edit.logo.submit')"
                    />
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    <x-logo-history
                        :logos="$team->logos()->orderByDesc('from')->get()"
                        folder="teams"
                        :add-url="route('admin.teams.logo.history.store', $team)"
                        :update-url="fn ($logo) => route('admin.teams.logo.history.update', [$team, $logo->id])"
                        :delete-url="fn ($logo) => route('admin.teams.logo.history.destroy', [$team, $logo->id])"
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

                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.edit.profile.title') }}</h2>

                    <form method="POST" action="{{ route('admin.teams.update', $team) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @include('team._profile-form', ['team' => $team])

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('team.edit.profile.submit') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="space-y-6">
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.teams.owner.title') }}</h2>

                <div class="space-y-2">
                    @forelse ($owners as $owner)
                        <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                            <div>
                                <p class="text-sm text-white font-semibold">
                                    {{ $owner->name }}
                                    @if ($owner->username)
                                        <span class="text-gray-500 font-normal">{{ '@'.$owner->username }}</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500">{{ $owner->email }}</p>
                            </div>
                            @can('teams.edit')
                                <form method="POST" action="{{ route('admin.teams.owner.destroy', [$team, $owner]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.teams.owner.remove')"
                                        :body="__('admin.teams.owner.remove_confirm', ['name' => $owner->name, 'team' => $team->name])"
                                        :trigger-label="__('admin.teams.owner.remove')"
                                        :submit-label="__('admin.teams.owner.remove')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">{{ __('admin.teams.no_owner') }}</p>
                    @endforelse
                </div>

                @can('teams.edit')
                    <x-modal :title="__('admin.teams.owner.add')" :open-by-default="$search !== ''">
                        <x-slot:trigger>
                            <button type="button"
                                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.teams.owner.add') }}
                            </button>
                        </x-slot:trigger>

                        <form method="GET" action="{{ route('admin.teams.show', $team) }}" class="flex gap-2">
                            <input type="text" name="q" x-ref="search" value="{{ $search }}" placeholder="{{ __('admin.teams.owner.search_placeholder') }}"
                                   class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.teams.owner.search_submit') }}
                            </button>
                        </form>

                        @if ($search)
                            <div class="space-y-2 pt-4">
                                @forelse ($searchResults as $found)
                                    <form method="POST" action="{{ route('admin.teams.owner.store', $team) }}" class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $found->id }}">
                                        <div>
                                            <p class="text-xs text-white font-semibold">
                                                {{ $found->name }}
                                                @if ($found->username)
                                                    <span class="text-gray-500 font-normal">{{ '@'.$found->username }}</span>
                                                @endif
                                            </p>
                                            <p class="text-[10px] text-gray-500">{{ $found->email }}</p>
                                        </div>
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                            {{ __('admin.teams.owner.assign') }}
                                        </button>
                                    </form>
                                @empty
                                    <p class="text-xs text-gray-500">{{ __('admin.teams.owner.search_empty') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </x-modal>
                @endcan
            </div>

            @can('teams.edit')
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.teams.max_permissions.title') }}</h2>
                    <p class="text-xs text-gray-500">
                        {!! __('admin.teams.max_permissions.help', ['link' => '<a href="'.route('teams.roles.index', $teamParams).'" class="text-gc-yellow hover:underline">'.__('admin.teams.max_permissions.link_text').'</a>']) !!}
                    </p>

                    <form method="POST" action="{{ route('admin.teams.max-permissions.update', $team) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @foreach ($permissionGroups as $group => $permissions)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-2">{{ Str::headline($group) }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center gap-2 text-sm text-gray-300 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                            <input type="checkbox" name="max_permissions[]" value="{{ $permission }}"
                                                   @checked(in_array($permission, $team->maxPermissions(), true))
                                                   class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
                                            {{ $permission }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.teams.max_permissions.save') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    @can('teams.edit')
        <div class="mt-6">
            <x-roster-panel
                :current="$roster"
                :history="$rosterHistory"
                :search="$playerSearch"
                :search-results="$playerSearchResults"
                :search-url="route('admin.teams.show', $team)"
                :add-url="route('admin.teams.roster.store', $team)"
                :update-url="fn ($entry) => route('admin.teams.roster.update', [$team, $entry->id])"
                :delete-url="fn ($entry) => route('admin.teams.roster.destroy', [$team, $entry->id])"
                :roles="__('team.roster.roles')"
                :title="__('team.roster.title')"
                :history-title="__('team.roster.history_title')"
                :add-label="__('team.roster.add')"
                :role-label="__('team.roster.role')"
                :joined-at-label="__('team.roster.joined_at')"
                :left-at-label="__('team.roster.left_at')"
                :save-label="__('team.roster.save')"
                :search-placeholder="__('team.roster.search_placeholder')"
                :search-submit-label="__('team.roster.search_submit')"
                :assign-label="__('team.roster.assign')"
                :remove-label="__('team.roster.remove')"
                :remove-confirm-title="__('team.roster.remove')"
                :remove-confirm-body="fn ($entry) => __('team.roster.remove_confirm', ['player' => $entry->player_handle])"
                :search-empty-label="__('team.roster.search_empty')"
                :current-empty-label="__('team.roster.current_empty')"
                :history-empty-label="__('team.roster.history_empty')"
                heading-tag="h2"
            />
        </div>
    @endcan
@endsection
