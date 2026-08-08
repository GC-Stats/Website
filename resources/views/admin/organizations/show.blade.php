{{--
    GC-Stats — Admin: organization detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $organization->name)

@section('content')
    @can('organizations.view')
        <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
            &larr; {{ __('admin.organizations.title') }}
        </a>
    @endcan

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $organization->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('organizations.show', [$organization->id, $organization->routeSlug()]) }}" target="_blank" rel="noopener"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.organizations.public_page') }}
            </a>
            <a href="{{ route('admin.organizations.roles.index', $organization) }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.organizations.roles_link') }} &rarr;
            </a>
            @can('organizations.delete')
                <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="__('admin.organizations.delete')"
                        :body="__('admin.organizations.delete').' — '.$organization->name"
                        :trigger-label="__('admin.organizations.delete')"
                        :submit-label="__('admin.organizations.delete')"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.organizations.title') }}</h2>
                @if ($canUploadLogo)
                    <x-admin.logo-upload-form
                        :current-url="$organization->logo"
                        :action-url="route('admin.organizations.logo.update', $organization)"
                        :submit-label="__('admin.organizations.form.save')"
                    />
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                @else
                    <img src="{{ $organization->logo }}" alt="" class="w-16 h-16 object-contain border border-white/10 rounded-lg bg-black/40 p-2">
                @endif
            </div>

            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.organizations.form.name_label') }}</h2>

                @if ($canEditProfile)
                    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.organizations.form.name_label') }}</label>
                            <input type="text" name="name" value="{{ $organization->name }}" required
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.organizations.form.slug_label') }}</label>
                            <input type="text" name="slug" value="{{ $organization->slug }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        <x-admin.country-select
                            name="country_code"
                            :label="__('admin.organizations.form.country_label')"
                            :selected="old('country_code', $organization->country_code)"
                            :countries="$countries"
                            :search-placeholder="__('player.edit.fields.country_code_search')"
                            :none-label="__('player.edit.fields.country_code_none')"
                        />
                        <div x-data="{ types: @js(old('types', $organization->types()) ?: ['']) }">
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.organizations.form.type_label') }}</label>
                            <template x-for="(type, index) in types" :key="index">
                                <div class="flex items-center gap-2 mb-2">
                                    <input type="text" :name="'types[' + index + ']'" x-model="types[index]"
                                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                    <button type="button" @click="types.splice(index, 1)"
                                            class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-3 py-2.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                                        &times;
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="types.push('')"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                +
                            </button>
                            @error('types')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                            @if ($errors->has('types.*'))
                                <p class="text-xs text-red-400 mt-2">{{ $errors->first('types.*') }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.organizations.form.liquipedia_label') }}</label>
                            <input type="url" name="liquipedia_link" value="{{ old('liquipedia_link', $organization->liquipedia_link) }}" placeholder="https://liquipedia.net/…"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            @error('liquipedia_link')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        @foreach (['twitter', 'twitch', 'instagram', 'youtube', 'tiktok', 'discord', 'website'] as $social)
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ Str::headline($social) }}</label>
                                <input type="text" name="socials[{{ $social }}]" value="{{ $organization->socials[$social] ?? '' }}"
                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            </div>
                        @endforeach

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.organizations.form.save') }}
                        </button>
                    </form>
                @else
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.organizations.form.name_label') }}</dt>
                            <dd class="text-white">{{ $organization->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.organizations.form.slug_label') }}</dt>
                            <dd class="text-gray-300">{{ $organization->slug }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.organizations.form.type_label') }}</dt>
                            <dd class="text-gray-300">{{ implode(', ', $organization->types()) ?: '—' }}</dd>
                        </div>
                        @if ($organization->liquipedia_link)
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.organizations.form.liquipedia_label') }}</dt>
                                <dd class="text-gray-300">{{ $organization->liquipedia_link }}</dd>
                            </div>
                        @endif
                        @foreach (['twitter', 'twitch', 'instagram', 'youtube', 'tiktok', 'discord', 'website'] as $social)
                            @if (! empty($organization->socials[$social]))
                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ Str::headline($social) }}</dt>
                                    <dd class="text-gray-300">{{ $organization->socials[$social] }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            @can('organizations.owner.manage')
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.organizations.owner.title') }}</h2>

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
                                <form method="POST" action="{{ route('admin.organizations.owner.destroy', [$organization, $owner]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.organizations.owner.remove')"
                                        :body="__('admin.organizations.owner.remove_confirm', ['name' => $owner->name, 'organization' => $organization->name])"
                                        :trigger-label="__('admin.organizations.owner.remove')"
                                        :submit-label="__('admin.organizations.owner.remove')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500">{{ __('admin.organizations.owner.no_owner') }}</p>
                        @endforelse
                    </div>

                    <x-modal :title="__('admin.organizations.owner.add')" :open-by-default="$search !== ''">
                        <x-slot:trigger>
                            <button type="button"
                                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.organizations.owner.add') }}
                            </button>
                        </x-slot:trigger>

                        <form method="GET" action="{{ route('admin.organizations.show', $organization) }}" class="flex gap-2">
                            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.organizations.owner.search_placeholder') }}"
                                   class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.organizations.owner.search_submit') }}
                            </button>
                        </form>

                        @if ($search)
                            <div class="space-y-2 pt-4">
                                @forelse ($searchResults as $found)
                                    <form method="POST" action="{{ route('admin.organizations.owner.store', $organization) }}" class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $found->id }}">
                                        <div>
                                            <p class="text-xs text-white font-semibold">{{ $found->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $found->email }}</p>
                                        </div>
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                            {{ __('admin.organizations.owner.assign') }}
                                        </button>
                                    </form>
                                @empty
                                    <p class="text-xs text-gray-500">{{ __('admin.organizations.owner.search_empty') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </x-modal>
                </div>
            @endcan

            @can('organizations.permissions.manage')
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.organizations.max_permissions.title') }}</h2>

                    <form method="POST" action="{{ route('admin.organizations.max-permissions.update', $organization) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @foreach ($permissionGroups as $group => $permissions)
                            @php $allGranted = count(array_diff($permissions, $organization->maxPermissions())) === 0; @endphp
                            <div x-data="{ toggleAll(checked) { $el.querySelectorAll('input[type=checkbox].permission-input').forEach(cb => cb.checked = checked) } }">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ Str::headline($group) }}</p>
                                    <label class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-400 cursor-pointer">
                                        <input type="checkbox" @checked($allGranted) @change="toggleAll($event.target.checked)"
                                               class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
                                        {{ __('admin.organizations.max_permissions.select_all') }}
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center gap-2 text-sm text-gray-300 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                            <input type="checkbox" name="max_permissions[]" value="{{ $permission }}"
                                                   @checked(in_array($permission, $organization->maxPermissions(), true))
                                                   class="permission-input rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
                                            {{ $permission }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.organizations.max_permissions.save') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    @if ($canManageStaff)
        <div class="mt-6">
            <x-admin.roster-panel
                :current="$currentStaff"
                :history="$staffHistory"
                :sync-url="route('admin.organizations.staff.sync', $organization)"
                :roles="\App\Helpers\StaffRoleLabel::options(\App\Support\StaffRoles::ORG_ROLES)"
                :title="__('admin.organizations.staff.title')"
                :history-title="__('admin.organizations.staff.history_title')"
                :add-label="__('admin.organizations.staff.add')"
                :role-label="__('admin.organizations.staff.role_label')"
                :joined-at-label="__('admin.organizations.staff.joined_at_label')"
                :left-at-label="__('admin.organizations.staff.left_at_label')"
                :save-label="__('admin.organizations.staff.save_label')"
                :remove-label="__('admin.organizations.staff.remove_label')"
                :remove-confirm-body="fn ($entry) => __('admin.organizations.staff.remove_confirm', ['staff' => $entry->staff_handle])"
                :current-empty-label="__('admin.organizations.staff.current_empty')"
                :history-empty-label="__('admin.organizations.staff.history_empty')"
                heading-tag="h2"
                picker-type="staff"
                pivot-field="staff_id"
            />
        </div>
    @endif
@endsection
