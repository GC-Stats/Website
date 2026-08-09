{{--
    GC-Stats — Admin: staff detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $staffMember->handle)

@section('content')
    <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.staff.title') }}
    </a>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $staffMember->handle }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('staff.show', [$staffMember->id, str($staffMember->handle)->slug()]) }}" target="_blank" rel="noopener"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.staff.public_page') }}
            </a>
        @can('staff.delete')
            <form method="POST" action="{{ route('admin.staff.destroy', $staffMember) }}">
                @csrf
                @method('DELETE')
                <x-confirm-modal
                    :title="__('admin.staff.delete.title')"
                    :body="__('admin.staff.delete.confirm_body', ['staff' => $staffMember->handle])"
                    :trigger-label="__('admin.staff.delete.trigger')"
                    :submit-label="__('admin.staff.delete.trigger')"
                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                />
            </form>
        @endcan
        </div>
    </div>

    <div class="space-y-6">
        @can('staff.edit')
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.staff.edit.logo.title') }}</h2>

                <x-admin.logo-upload-form
                    :current-url="$staffMember->photo"
                    :action-url="route('admin.staff.logo.update', $staffMember)"
                    :submit-label="__('admin.staff.edit.logo.submit')"
                />
                @error('logo')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.staff.edit.profile.title') }}</h2>

                <form method="POST" action="{{ route('admin.staff.update', $staffMember) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @php $socials = $staffMember->socials ?? []; @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.staff.fields.handle') }}</label>
                            <input type="text" name="handle" value="{{ old('handle', $staffMember->handle) }}" required
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('handle')<p class="text-xs text-red-400 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.staff.fields.first_name') }}</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $staffMember->first_name) }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.staff.fields.last_name') }}</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $staffMember->last_name) }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.staff.fields.pronouns') }}</label>
                            <x-styled-select name="pronouns" :selected="old('pronouns', $staffMember->pronouns)" :options="__('admin.staff.fields.pronouns_options')" />
                        </div>

                        <x-admin.country-select
                            name="country_code"
                            :label="__('admin.staff.fields.country_code')"
                            :selected="old('country_code', $staffMember->country_code)"
                            :countries="$countries"
                            :search-placeholder="__('player.edit.fields.country_code_search')"
                            :none-label="__('player.edit.fields.country_code_none')"
                        />

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.staff.fields.bio') }}</label>
                            <textarea name="bio" rows="3"
                                      class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('bio', $staffMember->bio) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.staff.fields.liquipedia_link') }}</label>
                            <input type="url" name="liquipedia_link" value="{{ old('liquipedia_link', $staffMember->liquipedia_link) }}" placeholder="https://liquipedia.net/…"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.staff.fields.vlr_id') }}</label>
                            <input type="number" name="vlr_id" value="{{ old('vlr_id', $staffMember->vlr_id) }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('vlr_id')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm text-gray-300">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $staffMember->is_active))
                                       class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
                                {{ __('admin.staff.fields.is_active') }}
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-white/10 space-y-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.staff.fields.socials') }}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach (['twitter', 'twitch', 'instagram', 'youtube', 'tiktok', 'discord'] as $platform)
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ ucfirst($platform) }}</label>
                                    <input type="text" name="socials[{{ $platform }}]" value="{{ old('socials.'.$platform, $socials[$platform] ?? '') }}"
                                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.staff.edit.profile.submit') }}
                    </button>
                </form>
            </div>

            <x-admin.roster-panel
                :current="$currentOrganizations"
                :history="$organizationHistory"
                :sync-url="route('admin.staff.organizations.sync', $staffMember)"
                :roles="\App\Helpers\StaffRoleLabel::options(\App\Support\StaffRoles::ORG_ROLES)"
                :title="__('admin.staff.organizations_panel.title')"
                :history-title="__('admin.staff.organizations_panel.history_title')"
                :add-label="__('admin.staff.organizations_panel.add')"
                :role-label="__('admin.organizations.staff.role_label')"
                :joined-at-label="__('admin.organizations.staff.joined_at_label')"
                :left-at-label="__('admin.organizations.staff.left_at_label')"
                :save-label="__('admin.organizations.staff.save_label')"
                :remove-label="__('admin.organizations.staff.remove_label')"
                :remove-confirm-body="fn ($entry) => __('admin.staff.organizations_panel.remove_confirm', ['organization' => $entry->organization_name])"
                :current-empty-label="__('admin.staff.organizations_panel.current_empty')"
                :history-empty-label="__('admin.staff.organizations_panel.history_empty')"
                heading-tag="h2"
                picker-type="organization"
                pivot-field="organization_id"
            />

            <x-admin.roster-panel
                :current="$currentTeams"
                :history="$teamHistory"
                :sync-url="route('admin.staff.teams.sync', $staffMember)"
                :roles="\App\Helpers\StaffRoleLabel::options(\App\Support\StaffRoles::TEAM_ROLES)"
                :title="__('admin.staff.teams_panel.title')"
                :history-title="__('admin.staff.teams_panel.history_title')"
                :add-label="__('admin.staff.teams_panel.add')"
                :role-label="__('admin.organizations.staff.role_label')"
                :joined-at-label="__('admin.organizations.staff.joined_at_label')"
                :left-at-label="__('admin.organizations.staff.left_at_label')"
                :save-label="__('admin.organizations.staff.save_label')"
                :remove-label="__('admin.organizations.staff.remove_label')"
                :remove-confirm-body="fn ($entry) => __('admin.staff.teams_panel.remove_confirm', ['team' => $entry->team_name])"
                :current-empty-label="__('admin.staff.teams_panel.current_empty')"
                :history-empty-label="__('admin.staff.teams_panel.history_empty')"
                heading-tag="h2"
                picker-type="team"
                pivot-field="team_id"
            />
        @endcan

        @can('staff.assignments.manage')
            <x-admin.xp-staff-panel
                :entries="$experienceEntries"
                :sync-url="route('admin.staff.experience.sync', $staffMember)"
                :title="__('admin.staff_experience.title')"
                :add-label="__('admin.staff_experience.add')"
                :save-label="__('admin.staff_experience.save')"
                :empty-label="__('admin.staff_experience.empty')"
                :remove-confirm-body="fn ($entry) => __('admin.staff_experience.remove_confirm', ['staff' => $staffMember->handle])"
            />
        @endcan

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.staff.linked_user.title') }}</h2>
            <p class="text-xs text-gray-500">{{ __('admin.staff.linked_user.body') }}</p>

            @if ($staffMember->user)
                <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <div>
                        <p class="text-sm text-white font-semibold">
                            {{ $staffMember->user->name }}
                            @if ($staffMember->user->username)
                                <span class="text-gray-500 font-normal">{{ '@'.$staffMember->user->username }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500">{{ $staffMember->user->email }}</p>
                    </div>
                    @can('staff.edit')
                        <form method="POST" action="{{ route('admin.staff.user.destroy', $staffMember) }}">
                            @csrf
                            @method('DELETE')
                            <x-confirm-modal
                                :title="__('admin.staff.linked_user.remove')"
                                :body="__('admin.staff.linked_user.remove_confirm', ['user' => $staffMember->user->name, 'staff' => $staffMember->handle])"
                                :trigger-label="__('admin.staff.linked_user.remove')"
                                :submit-label="__('admin.staff.linked_user.remove')"
                                trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                            />
                        </form>
                    @endcan
                </div>
            @else
                <p class="text-xs text-gray-500">{{ __('admin.staff.linked_user.no_user') }}</p>

                @can('staff.edit')
                    <x-modal :title="__('admin.staff.linked_user.add')" :open-by-default="$userSearch !== ''">
                        <x-slot:trigger>
                            <button type="button"
                                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.staff.linked_user.add') }}
                            </button>
                        </x-slot:trigger>

                        <form method="GET" action="{{ route('admin.staff.show', $staffMember) }}" class="flex gap-2">
                            <input type="text" name="user_q" value="{{ $userSearch }}" placeholder="{{ __('admin.staff.linked_user.search_placeholder') }}"
                                   class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.staff.linked_user.search_submit') }}
                            </button>
                        </form>

                        @if ($userSearch)
                            <div class="space-y-2 pt-4">
                                @forelse ($userSearchResults as $found)
                                    <form method="POST" action="{{ route('admin.staff.user.update', $staffMember) }}" class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                        @csrf
                                        @method('PUT')
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
                                            {{ __('admin.staff.linked_user.assign') }}
                                        </button>
                                    </form>
                                @empty
                                    <p class="text-xs text-gray-500">{{ __('admin.staff.linked_user.search_empty') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </x-modal>
                @endcan
            @endif
        </div>

        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.staff.linked_player.title') }}</h2>
            <p class="text-xs text-gray-500">{{ __('admin.staff.linked_player.body') }}</p>

            @if ($staffMember->player)
                <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <a href="{{ route('admin.players.show', $staffMember->player) }}" class="min-w-0 hover:opacity-80 transition">
                        <p class="text-sm text-white font-semibold">{{ $staffMember->player->handle }}</p>
                        <p class="text-xs text-gray-500">#{{ $staffMember->player->id }}</p>
                    </a>
                    @can('staff.edit')
                        <form method="POST" action="{{ route('admin.staff.player.destroy', $staffMember) }}">
                            @csrf
                            @method('DELETE')
                            <x-confirm-modal
                                :title="__('admin.staff.linked_player.remove')"
                                :body="__('admin.staff.linked_player.remove_confirm', ['player' => $staffMember->player->handle, 'staff' => $staffMember->handle])"
                                :trigger-label="__('admin.staff.linked_player.remove')"
                                :submit-label="__('admin.staff.linked_player.remove')"
                                trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                            />
                        </form>
                    @endcan
                </div>
            @else
                <p class="text-xs text-gray-500">{{ __('admin.staff.linked_player.no_player') }}</p>

                @can('staff.edit')
                    <x-modal :title="__('admin.staff.linked_player.add')" :open-by-default="$playerSearch !== ''">
                        <x-slot:trigger>
                            <button type="button"
                                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.staff.linked_player.add') }}
                            </button>
                        </x-slot:trigger>

                        <form method="GET" action="{{ route('admin.staff.show', $staffMember) }}" class="flex gap-2">
                            <input type="text" name="player_q" value="{{ $playerSearch }}" placeholder="{{ __('admin.staff.linked_player.search_placeholder') }}"
                                   class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.staff.linked_player.search_submit') }}
                            </button>
                        </form>

                        @if ($playerSearch)
                            <div class="space-y-2 pt-4">
                                @forelse ($playerSearchResults as $found)
                                    <form method="POST" action="{{ route('admin.staff.player.update', $staffMember) }}" class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="player_id" value="{{ $found->id }}">
                                        <div>
                                            <p class="text-xs text-white font-semibold">{{ $found->handle }}</p>
                                            <p class="text-[10px] text-gray-500">#{{ $found->id }}</p>
                                        </div>
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                            {{ __('admin.staff.linked_player.assign') }}
                                        </button>
                                    </form>
                                @empty
                                    <p class="text-xs text-gray-500">{{ __('admin.staff.linked_player.search_empty') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </x-modal>
                @endcan
            @endif
        </div>
    </div>
@endsection
