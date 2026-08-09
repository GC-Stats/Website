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

    <div class="grid grid-cols-1 gap-6">
        <div class="space-y-6">
            @can('teams.edit')
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.edit.logo.title') }}</h2>

                    <x-admin.logo-upload-form
                        :current-url="$team->logo"
                        :action-url="route('admin.teams.logo.update', $team)"
                        :submit-label="__('team.edit.logo.submit')"
                        :themeable="true"
                        :theme-universal-label="__('team.edit.logo.theme_universal')"
                        :theme-dark-label="__('team.edit.logo.theme_dark')"
                        :theme-light-label="__('team.edit.logo.theme_light')"
                    />
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    <x-admin.logo-history
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
                        :themeable="true"
                        :theme-universal-label="__('team.edit.logo.theme_universal')"
                        :theme-dark-label="__('team.edit.logo.theme_dark')"
                        :theme-light-label="__('team.edit.logo.theme_light')"
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

                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('team.edit.tags.title') }}</h2>
                    <p class="text-xs text-gray-500">{{ __('team.edit.tags.body') }}</p>

                    <form method="POST" action="{{ route('admin.teams.tags.update', $team) }}" class="space-y-3"
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
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('team.edit.tags.submit') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    @can('teams.edit')
        <div class="mt-6">
            <x-admin.roster-panel
                :current="$roster"
                :history="$rosterHistory"
                :sync-url="route('admin.teams.roster.sync', $team)"
                :roles="__('team.roster.roles')"
                :title="__('team.roster.title')"
                :history-title="__('team.roster.history_title')"
                :add-label="__('team.roster.add')"
                :role-label="__('team.roster.role')"
                :joined-at-label="__('team.roster.joined_at')"
                :left-at-label="__('team.roster.left_at')"
                :save-label="__('team.roster.save')"
                :remove-label="__('team.roster.remove')"
                :remove-confirm-body="fn ($entry) => __('team.roster.remove_confirm', ['player' => $entry->player_handle])"
                :current-empty-label="__('team.roster.current_empty')"
                :history-empty-label="__('team.roster.history_empty')"
                heading-tag="h2"
            />
        </div>
    @endcan

    @can('teams.edit')
        <div class="mt-6">
            <x-admin.roster-panel
                :current="$currentStaff"
                :history="$staffHistory"
                :sync-url="route('admin.teams.staff.sync', $team)"
                :roles="\App\Helpers\StaffRoleLabel::options(\App\Support\StaffRoles::TEAM_ROLES)"
                :title="__('admin.teams.staff_panel.title')"
                :history-title="__('admin.teams.staff_panel.history_title')"
                :add-label="__('admin.teams.staff_panel.add')"
                :role-label="__('admin.organizations.staff.role_label')"
                :joined-at-label="__('admin.organizations.staff.joined_at_label')"
                :left-at-label="__('admin.organizations.staff.left_at_label')"
                :save-label="__('admin.organizations.staff.save_label')"
                :remove-label="__('admin.organizations.staff.remove_label')"
                :remove-confirm-body="fn ($entry) => __('admin.teams.staff_panel.remove_confirm', ['staff' => $entry->staff_handle])"
                :current-empty-label="__('admin.teams.staff_panel.current_empty')"
                :history-empty-label="__('admin.teams.staff_panel.history_empty')"
                heading-tag="h2"
                picker-type="staff"
                pivot-field="staff_id"
            />
        </div>
    @endcan
@endsection
