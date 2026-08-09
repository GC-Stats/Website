{{--
    GC-Stats — Organization dashboard: edit

    The editable profile/logo/staff-roster form — split out from the
    overview page (index.blade.php), which is now a read-only summary.
    Reached only when the current user can edit at least one of these
    (see DashboardController::canEditAnything()), so each section below
    only needs to check its own specific permission to decide between the
    form and a read-only fallback.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', __('organization.dashboard.edit.title').' — '.$organization->name)

@section('content')
    <a href="{{ route('organization-dashboard.index', $organization) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $organization->name }}
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('organization.dashboard.logo.title') }}</h2>
                @if ($canUploadLogo)
                    <x-admin.logo-upload-form
                        :current-url="$organization->logo"
                        :action-url="route('organization-dashboard.logo.update', $organization)"
                        :submit-label="__('organization.dashboard.logo.submit')"
                    />
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                @else
                    <img src="{{ $organization->logo }}" alt="" class="w-16 h-16 object-contain border border-white/10 rounded-lg bg-black/40 p-2">
                @endif
            </div>

            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('organization.dashboard.profile.title') }}</h2>

                @if ($canEditProfile)
                    <form method="POST" action="{{ route('organization-dashboard.update', $organization) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.organizations.form.name_label') }}</label>
                            <input type="text" name="name" value="{{ old('name', $organization->name) }}" required
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.organizations.form.slug_label') }}</label>
                            <input type="text" name="slug" value="{{ old('slug', $organization->slug) }}"
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
                            {{ __('organization.dashboard.profile.submit') }}
                        </button>
                    </form>
                @else
                    <p class="text-xs text-gray-500">{{ __('organization.dashboard.profile.no_permission') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6">
        @if ($canManageStaff)
            <x-admin.roster-panel
                :current="$currentStaff"
                :history="$staffHistory"
                :sync-url="route('organization-dashboard.staff.sync', $organization)"
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
        @endif
    </div>
@endsection
