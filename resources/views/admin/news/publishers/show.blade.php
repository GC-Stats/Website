{{--
    GC-Stats — Admin: publisher detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $publisher->name)

@section('content')
    @can('news.publishers.view')
        <a href="{{ route('admin.news.publishers.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
            &larr; {{ __('admin.news.publishers.title') }}
        </a>
    @endcan

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $publisher->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.news.publishers.roles.index', $publisher) }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('admin.news.publishers.roles_link') }} &rarr;
            </a>
            @can('news.publishers.delete')
                <form method="POST" action="{{ route('admin.news.publishers.destroy', $publisher) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="__('admin.news.publishers.delete')"
                        :body="__('admin.news.publishers.delete').' — '.$publisher->name"
                        :trigger-label="__('admin.news.publishers.delete')"
                        :submit-label="__('admin.news.publishers.delete')"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Logo --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.publishers.title') }} — {{ __('admin.teams.title') }}</h2>
                @if ($canUploadLogo)
                    <x-logo-upload-form
                        :current-url="$publisher->logo"
                        :action-url="route('admin.news.publishers.logo.update', $publisher)"
                        :submit-label="__('admin.news.publishers.form.save')"
                    />
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                @else
                    <img src="{{ $publisher->logo }}" alt="" class="w-16 h-16 object-contain border border-white/10 rounded-lg bg-black/40 p-2">
                @endif
            </div>

            {{-- Profile --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.publishers.form.name_label') }}</h2>

                @if ($canEditProfile)
                    <form method="POST" action="{{ route('admin.news.publishers.update', $publisher) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.publishers.form.name_label') }}</label>
                            <input type="text" name="name" value="{{ $publisher->name }}" required
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.publishers.form.slug_label') }}</label>
                            <input type="text" name="slug" value="{{ $publisher->slug }}"
                                   class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        @foreach (['website', 'twitter', 'discord', 'instagram', 'youtube'] as $social)
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ Str::headline($social) }}</label>
                                <input type="text" name="socials[{{ $social }}]" value="{{ $publisher->socials[$social] ?? '' }}"
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            </div>
                        @endforeach

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('admin.news.publishers.form.save') }}
                        </button>
                    </form>
                @else
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.news.publishers.form.name_label') }}</dt>
                            <dd class="text-white">{{ $publisher->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.news.publishers.form.slug_label') }}</dt>
                            <dd class="text-gray-300">{{ $publisher->slug }}</dd>
                        </div>
                        @foreach (['website', 'twitter', 'discord', 'instagram', 'youtube'] as $social)
                            @if (! empty($publisher->socials[$social]))
                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ Str::headline($social) }}</dt>
                                    <dd class="text-gray-300">{{ $publisher->socials[$social] }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            {{-- Owner --}}
            @can('news.publishers.owner.manage')
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.publishers.owner.title') }}</h2>

                    <div class="space-y-2">
                        @forelse ($owners as $owner)
                            <div class="flex items-center justify-between gap-4 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                                <div>
                                    <p class="text-sm text-white font-semibold">
                                        {{ $owner->name }}
                                        @if ($owner->username)
                                            <span class="text-gray-500 font-normal">{{ '@'.$owner->username }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $owner->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.news.publishers.owner.destroy', [$publisher, $owner]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.news.publishers.owner.remove')"
                                        :body="__('admin.news.publishers.owner.remove_confirm', ['name' => $owner->name, 'publisher' => $publisher->name])"
                                        :trigger-label="__('admin.news.publishers.owner.remove')"
                                        :submit-label="__('admin.news.publishers.owner.remove')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500">{{ __('admin.news.publishers.owner.no_owner') }}</p>
                        @endforelse
                    </div>

                    <x-modal :title="__('admin.news.publishers.owner.add')" :open-by-default="$search !== ''">
                        <x-slot:trigger>
                            <button type="button"
                                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                {{ __('admin.news.publishers.owner.add') }}
                            </button>
                        </x-slot:trigger>

                        <form method="GET" action="{{ route('admin.news.publishers.show', $publisher) }}" class="flex gap-2">
                            <input type="text" name="q" x-ref="search" value="{{ $search }}" placeholder="{{ __('admin.news.publishers.owner.search_placeholder') }}"
                                   class="flex-1 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                {{ __('admin.news.publishers.owner.search_submit') }}
                            </button>
                        </form>

                        @if ($search)
                            <div class="space-y-2 pt-4">
                                @forelse ($searchResults as $found)
                                    <form method="POST" action="{{ route('admin.news.publishers.owner.store', $publisher) }}" class="flex items-center justify-between gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $found->id }}">
                                        <div>
                                            <p class="text-xs text-white font-semibold">{{ $found->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $found->email }}</p>
                                        </div>
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                                            {{ __('admin.news.publishers.owner.assign') }}
                                        </button>
                                    </form>
                                @empty
                                    <p class="text-xs text-gray-500">{{ __('admin.news.publishers.owner.search_empty') }}</p>
                                @endforelse
                            </div>
                        @endif
                    </x-modal>
                </div>
            @endcan

            {{-- Max permissions ceiling --}}
            @can('news.publishers.edit')
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.publishers.max_permissions.title') }}</h2>

                    <form method="POST" action="{{ route('admin.news.publishers.max-permissions.update', $publisher) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @foreach ($permissionGroups as $group => $permissions)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-2">{{ Str::headline($group) }}</p>
                                <div class="grid grid-cols-1 gap-2">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                                            <input type="checkbox" name="max_permissions[]" value="{{ $permission }}"
                                                   @checked(in_array($permission, $publisher->maxPermissions(), true))
                                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                            {{ $permission }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('admin.news.publishers.max_permissions.save') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
@endsection
