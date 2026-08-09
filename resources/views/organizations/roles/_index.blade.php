{{--
    GC-Stats — Organization roles list (shared content)

    Shared between admin/organizations/roles/index.blade.php (site-admin
    view, extends admin.layout) and organization/dashboard/roles/index.blade.php
    (the organization's own dashboard, extends organization.layout) — same
    Organization\RoleController action renders either wrapper depending on
    which route matched, both @include this partial so the actual markup
    never has to be duplicated. Expects $organization, $roles, $routePrefix
    (e.g. "admin.organizations.roles." or "organization-dashboard.roles."),
    $backUrl, $backLabel.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-4">
    &larr; {{ $backLabel }}
</a>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ __('admin.organizations.roles.title') }}</h1>

    <x-modal :title="__('admin.organizations.roles.new_role.title')">
        <x-slot:trigger>
            <button type="button"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                {{ __('admin.organizations.roles.new_role.title') }}
            </button>
        </x-slot:trigger>

        <form method="POST" action="{{ route($routePrefix.'store', $organization) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('admin.organizations.roles.new_role.name_label') }}
                </label>
                <input type="text" name="name" required
                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                @error('name')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                {{ __('admin.organizations.roles.new_role.submit') }}
            </button>
        </form>
    </x-modal>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    @foreach ($roles as $role)
        <a href="{{ route($routePrefix.'show', [$organization, $role]) }}"
           class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl hover:border-gc-yellow/50 transition-all group">
            <h2 class="text-sm font-black uppercase tracking-widest text-white group-hover:text-gc-yellow transition-colors mb-2">{{ $role->name }}</h2>
            <p class="text-xs text-gray-500">{{ trans_choice('admin.organizations.roles.member_count', $role->users_count, ['count' => $role->users_count]) }}</p>
        </a>
    @endforeach
</div>
