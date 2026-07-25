{{--
    GC-Stats — Role permission matrix

    Shared by admin/roles/show and team/roles/show: a grid of permission
    checkboxes grouped by section, or a placeholder message when there's
    nothing editable (a protected global role, or a team with an empty
    max_permissions ceiling).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'role',
    'permissionGroups',
    'updateUrl',
    'title',
    'saveLabel',
    'headingTag' => 'h3',
    'editable' => true,
    'emptyMessage' => null,
])

<div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
    <{{ $headingTag }} class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</{{ $headingTag }}>

    @if (!$editable || empty($permissionGroups))
        <p class="text-xs text-gray-500">{{ $emptyMessage }}</p>
    @else
        <form method="POST" action="{{ $updateUrl }}" class="space-y-6">
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
                {{ $saveLabel }}
            </button>
        </form>
    @endif
</div>
