{{--
    GC-Stats — Admin: team detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $team->name)

@section('content')
    <a href="{{ route('admin.teams.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.teams.title') }}
    </a>

    @php $teamParams = [$team, $team->routeSlug()]; @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $team->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('teams.edit', $teamParams) }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('team.edit.title') }}
            </a>
            <a href="{{ route('teams.roles.index', $teamParams) }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('admin.nav.roles') }} &rarr;
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3 mb-6">
            {{ __('admin.status.'.session('status')) }}
        </div>
    @endif
    @error('role')
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3 mb-6">{{ $message }}</div>
    @enderror

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Max permissions ceiling --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.teams.max_permissions.title') }}</h2>
                <p class="text-xs text-gray-500">
                    {!! __('admin.teams.max_permissions.help', ['link' => '<a href="'.route('teams.roles.index', $teamParams).'" class="text-gc-yellow hover:underline">'.__('admin.teams.max_permissions.link_text').'</a>']) !!}
                </p>

                <form method="POST" action="{{ route('admin.teams.max-permissions.update', $team) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @foreach ($permissionGroups as $group => $permissions)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-2">{{ Str::headline($group) }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                                        <input type="checkbox" name="max_permissions[]" value="{{ $permission }}"
                                               @checked(in_array($permission, $team->maxPermissions(), true))
                                               class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                        {{ $permission }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('admin.teams.max_permissions.save') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Owner --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.teams.owner.title') }}</h2>

                <div class="space-y-2">
                    @forelse ($owners as $owner)
                        <div class="flex items-center justify-between gap-4 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                            <div>
                                <p class="text-sm text-white font-semibold">{{ $owner->name }}</p>
                                <p class="text-xs text-gray-500">{{ $owner->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.teams.owner.destroy', [$team, $owner]) }}">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="__('admin.teams.owner.remove')"
                                    :body="__('admin.teams.owner.remove_confirm', ['name' => $owner->name, 'team' => $team->name])"
                                    :trigger-label="__('admin.teams.owner.remove')"
                                    :submit-label="__('admin.teams.owner.remove')"
                                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">{{ __('admin.teams.no_owner') }}</p>
                    @endforelse
                </div>

                <x-modal :title="__('admin.teams.owner.add')" :open-by-default="$search !== ''">
                    <x-slot:trigger>
                        <button type="button"
                                class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('admin.teams.owner.add') }}
                        </button>
                    </x-slot:trigger>

                    <form method="GET" action="{{ route('admin.teams.show', $team) }}" class="flex gap-2">
                        <input type="text" name="q" x-ref="search" value="{{ $search }}" placeholder="{{ __('admin.teams.owner.search_placeholder') }}"
                               class="flex-1 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        <button type="submit"
                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                            {{ __('admin.teams.owner.search_submit') }}
                        </button>
                    </form>

                    @if ($search)
                        <div class="space-y-2 pt-4">
                            @forelse ($searchResults as $found)
                                <form method="POST" action="{{ route('admin.teams.owner.store', $team) }}" class="flex items-center justify-between gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $found->id }}">
                                    <div>
                                        <p class="text-xs text-white font-semibold">{{ $found->name }}</p>
                                        <p class="text-[10px] text-gray-500">{{ $found->email }}</p>
                                    </div>
                                    <button type="submit"
                                            class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                                        {{ __('admin.teams.owner.assign') }}
                                    </button>
                                </form>
                            @empty
                                <p class="text-xs text-gray-500">{{ __('admin.teams.owner.search_empty') }}</p>
                            @endforelse
                        </div>
                    @endif
                </x-modal>
            </div>
        </div>
    </div>
@endsection
