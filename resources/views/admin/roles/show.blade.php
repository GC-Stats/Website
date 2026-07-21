{{--
    GC-Stats — Admin: role detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $role->name)

@php $protected = $role->name === 'super-admin'; @endphp

@section('content')
    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.roles.title') }}
    </a>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $role->name }}</h2>

        @unless ($protected)
            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}">
                @csrf
                @method('DELETE')
                <x-confirm-modal
                    :title="__('admin.roles.delete')"
                    :body="__('admin.roles.delete_confirm', ['role' => $role->name])"
                    :trigger-label="__('admin.roles.delete')"
                    :submit-label="__('admin.roles.delete')"
                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                />
            </form>
        @endunless
    </div>

    @if ($protected)
        <div class="bg-gc-yellow/10 border border-gc-yellow/30 text-gc-yellow text-sm rounded-lg px-4 py-3 mb-6">
            {{ __('admin.roles.protected_note') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-role-permissions-form
                :role="$role"
                :permission-groups="$permissionGroups"
                :update-url="route('admin.roles.update', $role)"
                :title="__('admin.roles.permissions.title')"
                :save-label="__('admin.roles.permissions.save')"
                :editable="!$protected"
                :empty-message="__('admin.roles.protected_note')"
            />

            <x-role-members-panel
                :members="$members"
                :search="$search"
                :search-results="$searchResults"
                :search-url="route('admin.roles.show', $role)"
                :add-member-url="route('admin.roles.members.store', $role)"
                :remove-member-url="fn ($member) => route('admin.roles.members.destroy', [$role, $member])"
                :title="__('admin.roles.members.title')"
                :add-label="__('admin.roles.members.add')"
                :search-placeholder="__('admin.roles.members.search_placeholder')"
                :search-submit-label="__('admin.roles.members.search_submit')"
                :assign-label="__('admin.roles.members.assign')"
                :remove-label="__('admin.roles.members.remove')"
                :remove-confirm-title="__('admin.roles.members.remove')"
                :remove-confirm-body="fn ($member) => __('admin.roles.members.remove_confirm', ['role' => $role->name, 'name' => $member->name])"
                :search-empty-label="__('admin.roles.members.search_empty')"
                :members-empty-label="__('admin.roles.members.empty')"
            />
        </div>

        <div class="space-y-6">
            @unless ($protected)
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.roles.discord_mapping.title') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('admin.roles.discord_mapping.help') }}</p>

                    @if ($discordMapping)
                        <p class="text-xs text-white">{{ __('admin.roles.discord_mapping.current', ['id' => $discordMapping->discord_role_id]) }}</p>
                        @if ($discordMapping->discord_role_name)
                            <p class="text-xs text-gray-500">{{ $discordMapping->discord_role_name }}</p>
                        @endif
                    @else
                        <p class="text-xs text-gray-500">{{ __('admin.roles.discord_mapping.none') }}</p>
                    @endif

                    <form method="POST" action="{{ route('admin.roles.discord-mapping.update', $role) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('admin.roles.discord_mapping.id_label') }}
                            </label>
                            <input type="text" name="discord_role_id" value="{{ old('discord_role_id', $discordMapping?->discord_role_id) }}" required
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('discord_role_id')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('admin.roles.discord_mapping.name_label') }}
                            </label>
                            <input type="text" name="discord_role_name" value="{{ old('discord_role_name', $discordMapping?->discord_role_name) }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.roles.discord_mapping.save') }}
                        </button>
                    </form>

                    @if ($discordMapping)
                        <form method="POST" action="{{ route('admin.roles.discord-mapping.destroy', $role) }}">
                            @csrf
                            @method('DELETE')
                            <x-confirm-modal
                                :title="__('admin.roles.discord_mapping.remove')"
                                :body="__('admin.roles.discord_mapping.remove_confirm')"
                                :trigger-label="__('admin.roles.discord_mapping.remove')"
                                :submit-label="__('admin.roles.discord_mapping.remove')"
                                trigger-class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                            />
                        </form>
                    @endif
                </div>
            @endunless
        </div>
    </div>
@endsection
