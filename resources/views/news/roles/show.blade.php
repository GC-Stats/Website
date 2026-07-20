{{--
    GC-Stats — Admin: publisher role detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $role->name)

@php
    $ownerRole = $role->name === \App\Services\PublisherRoleService::ROLE_OWNER;
@endphp

@section('content')
    <a href="{{ route('admin.news.publishers.roles.index', $publisher) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition">
        &larr; {{ __('admin.news.roles.title') }}
    </a>

    <div class="flex items-center justify-between mt-4 mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $role->name }}</h1>

        @unless ($ownerRole)
            <form method="POST" action="{{ route('admin.news.publishers.roles.destroy', [$publisher, $role]) }}">
                @csrf
                @method('DELETE')
                <x-confirm-modal
                    :title="__('admin.news.roles.delete')"
                    :body="__('admin.news.roles.delete_confirm', ['role' => $role->name])"
                    :trigger-label="__('admin.news.roles.delete')"
                    :submit-label="__('admin.news.roles.delete')"
                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                />
            </form>
        @endunless
    </div>

    <div class="space-y-6">
        <x-role-permissions-form
            :role="$role"
            :permission-groups="$permissionGroups"
            :update-url="route('admin.news.publishers.roles.update', [$publisher, $role])"
            :title="__('admin.news.roles.permissions.title')"
            :save-label="__('admin.news.roles.permissions.save')"
            :editable="! $ownerRole"
            :empty-message="__('admin.news.roles.permissions.empty_ceiling')"
        />

        <x-role-members-panel
            :members="$members"
            :search="$search"
            :search-results="$searchResults"
            :search-url="route('admin.news.publishers.roles.show', [$publisher, $role])"
            :add-member-url="route('admin.news.publishers.roles.members.store', [$publisher, $role])"
            :remove-member-url="fn ($member) => route('admin.news.publishers.roles.members.destroy', [$publisher, $role, $member])"
            :title="__('admin.news.roles.members.title')"
            :add-label="__('admin.news.roles.members.add')"
            :search-placeholder="__('admin.news.roles.members.search_placeholder')"
            :search-submit-label="__('admin.news.roles.members.search_submit')"
            :assign-label="__('admin.news.roles.members.assign')"
            :remove-label="__('admin.news.roles.members.remove')"
            :remove-confirm-title="__('admin.news.roles.members.remove')"
            :remove-confirm-body="fn ($member) => __('admin.news.roles.members.remove_confirm', ['role' => $role->name, 'name' => $member->name])"
            :search-empty-label="__('admin.news.roles.members.search_empty')"
            :members-empty-label="__('admin.news.roles.members.empty')"
            :can-add="! $ownerRole"
        />
    </div>
@endsection
