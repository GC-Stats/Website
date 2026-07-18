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
                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                />
            </form>
        @endunless
    </div>

    @if ($protected)
        <div class="bg-gc-yellow/10 border border-gc-yellow/30 text-gc-yellow text-sm rounded-sm px-4 py-3 mb-6">
            {{ __('admin.roles.protected_note') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Permissions --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.roles.permissions.title') }}</h3>

                @if ($protected)
                    <p class="text-xs text-gray-500">{{ __('admin.roles.protected_note') }}</p>
                @else
                    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @foreach ($permissionGroups as $group => $permissions)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-2">{{ Str::headline($group) }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                                   @checked($role->permissions->contains('name', $permission))
                                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                            {{ $permission }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('admin.roles.permissions.save') }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- Members --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.roles.members.title') }}</h3>

                <div class="space-y-2">
                    @forelse ($members as $member)
                        <div class="flex items-center justify-between gap-4 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                            <div>
                                <p class="text-sm text-white font-semibold">{{ $member->name }}</p>
                                <p class="text-xs text-gray-500">{{ $member->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.roles.members.destroy', [$role, $member]) }}">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="__('admin.roles.members.remove')"
                                    :body="__('admin.roles.members.remove_confirm', ['role' => $role->name, 'name' => $member->name])"
                                    :trigger-label="__('admin.roles.members.remove')"
                                    :submit-label="__('admin.roles.members.remove')"
                                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">{{ __('admin.roles.members.empty') }}</p>
                    @endforelse
                </div>

                <x-admin::modal :title="__('admin.roles.members.add')">
                    <x-slot:trigger>
                        <button type="button"
                                class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('admin.roles.members.add') }}
                        </button>
                    </x-slot:trigger>

                    <form method="GET" action="{{ route('admin.roles.show', $role) }}" class="flex gap-2">
                        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.roles.members.search_placeholder') }}"
                               class="flex-1 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        <button type="submit"
                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('admin.roles.members.search_submit') }}
                        </button>
                    </form>

                    @if ($search)
                        <div class="space-y-2 pt-4">
                            @forelse ($searchResults as $found)
                                <form method="POST" action="{{ route('admin.roles.members.store', $role) }}" class="flex items-center justify-between gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $found->id }}">
                                    <div>
                                        <p class="text-xs text-white font-semibold">{{ $found->name }}</p>
                                        <p class="text-[10px] text-gray-500">{{ $found->email }}</p>
                                    </div>
                                    <button type="submit"
                                            class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                                        {{ __('admin.roles.members.assign') }}
                                    </button>
                                </form>
                            @empty
                                <p class="text-xs text-gray-500">{{ __('admin.roles.members.search_empty') }}</p>
                            @endforelse
                        </div>
                    @endif
                </x-admin::modal>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Discord auto-assignment --}}
            @unless ($protected)
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
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
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('discord_role_id')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('admin.roles.discord_mapping.name_label') }}
                            </label>
                            <input type="text" name="discord_role_name" value="{{ old('discord_role_name', $discordMapping?->discord_role_name) }}"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
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
                                trigger-class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                            />
                        </form>
                    @endif
                </div>
            @endunless
        </div>
    </div>
@endsection
