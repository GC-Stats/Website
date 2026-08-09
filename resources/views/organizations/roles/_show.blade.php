{{--
    GC-Stats — Organization role detail (shared content)

    Shared between admin/organizations/roles/show.blade.php and
    organization/dashboard/roles/show.blade.php — see _index.blade.php's
    docblock. Expects $organization, $role, $permissionGroups, $members,
    $search, $searchResults, $routePrefix, $backUrl.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $ownerRole = $role->name === \App\Services\OrganizationRoleService::ROLE_OWNER;
@endphp

<a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition">
    &larr; {{ __('admin.organizations.roles.title') }}
</a>

<div class="flex items-center justify-between mt-4 mb-6">
    <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $role->name }}</h1>

    @unless ($ownerRole)
        <form method="POST" action="{{ route($routePrefix.'destroy', [$organization, $role]) }}">
            @csrf
            @method('DELETE')
            <x-confirm-modal
                :title="__('admin.organizations.roles.delete')"
                :body="__('admin.organizations.roles.delete_confirm', ['role' => $role->name])"
                :trigger-label="__('admin.organizations.roles.delete')"
                :submit-label="__('admin.organizations.roles.delete')"
                trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
            />
        </form>
    @endunless
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-admin.role-permissions-form
            :role="$role"
            :permission-groups="$permissionGroups"
            :update-url="route($routePrefix.'update', [$organization, $role])"
            :title="__('admin.organizations.roles.permissions.title')"
            :save-label="__('admin.organizations.roles.permissions.save')"
            :editable="! $ownerRole"
            :empty-message="__('admin.organizations.roles.permissions.empty_ceiling')"
        />
    </div>

    <div>
        <x-admin.role-members-panel
            :members="$members"
            :search="$search"
            :search-results="$searchResults"
            :search-url="route($routePrefix.'show', [$organization, $role])"
            :add-member-url="route($routePrefix.'members.store', [$organization, $role])"
            :remove-member-url="fn ($member) => route($routePrefix.'members.destroy', [$organization, $role, $member])"
            :title="__('admin.organizations.roles.members.title')"
            :add-label="__('admin.organizations.roles.members.add')"
            :search-placeholder="__('admin.organizations.roles.members.search_placeholder')"
            :search-submit-label="__('admin.organizations.roles.members.search_submit')"
            :assign-label="__('admin.organizations.roles.members.assign')"
            :remove-label="__('admin.organizations.roles.members.remove')"
            :remove-confirm-title="__('admin.organizations.roles.members.remove')"
            :remove-confirm-body="fn ($member) => __('admin.organizations.roles.members.remove_confirm', ['role' => $role->name, 'name' => $member->name])"
            :search-empty-label="__('admin.organizations.roles.members.search_empty')"
            :members-empty-label="__('admin.organizations.roles.members.empty')"
            :can-add="! $ownerRole"
        />
    </div>
</div>
