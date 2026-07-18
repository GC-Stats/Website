{{--
    GC-Stats — Team: role detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', $role->name)

@php
    $ownerRole = $role->name === \App\Services\TeamRoleService::ROLE_OWNER;
    $teamParams = [$team, $team->routeSlug()];
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <a href="{{ route('teams.roles.index', $teamParams) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition">
                &larr; {{ __('team.roles.title') }}
            </a>

            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $role->name }}</h1>

                @unless ($ownerRole)
                    <form method="POST" action="{{ route('teams.roles.destroy', [...$teamParams, $role]) }}">
                        @csrf
                        @method('DELETE')
                        <x-confirm-modal
                            :title="__('team.roles.delete')"
                            :body="__('team.roles.delete_confirm', ['role' => $role->name])"
                            :trigger-label="__('team.roles.delete')"
                            :submit-label="__('team.roles.delete')"
                            trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                        />
                    </form>
                @endunless
            </div>

            @if (session('status'))
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __('team.roles.status.'.session('status')) }}
                </div>
            @endif
            @error('role')
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3">{{ $message }}</div>
            @enderror

            <x-role-permissions-form
                :role="$role"
                :permission-groups="$permissionGroups"
                :update-url="route('teams.roles.update', [...$teamParams, $role])"
                :title="__('team.roles.permissions.title')"
                :save-label="__('team.roles.permissions.save')"
                :empty-message="__('team.roles.permissions.empty_ceiling')"
                heading-tag="h2"
            />

            <x-role-members-panel
                :members="$members"
                :search="$search"
                :search-results="$searchResults"
                :search-url="route('teams.roles.show', [...$teamParams, $role])"
                :add-member-url="route('teams.roles.members.store', [...$teamParams, $role])"
                :remove-member-url="fn ($member) => route('teams.roles.members.destroy', [...$teamParams, $role, $member])"
                :title="__('team.roles.members.title')"
                :add-label="__('team.roles.members.add')"
                :search-placeholder="__('team.roles.members.search_placeholder')"
                :search-submit-label="__('team.roles.members.search_submit')"
                :assign-label="__('team.roles.members.assign')"
                :remove-label="__('team.roles.members.remove')"
                :remove-confirm-title="__('team.roles.members.remove')"
                :remove-confirm-body="fn ($member) => __('team.roles.members.remove_confirm', ['role' => $role->name, 'name' => $member->name])"
                :search-empty-label="__('team.roles.members.search_empty')"
                :members-empty-label="__('team.roles.members.empty')"
                heading-tag="h2"
            />
        </section>
    </div>
@endsection
