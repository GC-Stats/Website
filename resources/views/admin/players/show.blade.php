{{--
    GC-Stats — Admin: player detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $player->handle)

@section('content')
    <a href="{{ route('admin.players.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.players.title') }}
    </a>

    @php $playerParams = [$player->id, str($player->handle)->slug()]; @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $player->handle }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('players.show', $playerParams) }}" target="_blank" rel="noopener"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.players.public_page') }}
            </a>
            @can('players.merge')
                <a href="{{ route('admin.players.merge.show', $player) }}"
                   class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                    {{ __('admin.players.merge.trigger') }}
                </a>
            @endcan
            @can('players.delete')
                <form method="POST" action="{{ route('admin.players.destroy', $player) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="__('admin.players.delete.title')"
                        :body="__('admin.players.delete.confirm_body', ['player' => $player->handle])"
                        :trigger-label="__('admin.players.delete.trigger')"
                        :submit-label="__('admin.players.delete.trigger')"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @can('players.edit')
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.edit.logo.title') }}</h2>

                    <x-logo-upload-form
                        :current-url="$player->profile_photo"
                        :action-url="route('admin.players.logo.update', $player)"
                        :submit-label="__('player.edit.logo.submit')"
                    />
                    @error('logo')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    <x-logo-history
                        :logos="$player->logos()->orderByDesc('from')->get()"
                        folder="players"
                        :add-url="route('admin.players.logo.history.store', $player)"
                        :update-url="fn ($logo) => route('admin.players.logo.history.update', [$player, $logo->id])"
                        :delete-url="fn ($logo) => route('admin.players.logo.history.destroy', [$player, $logo->id])"
                        :title="__('player.edit.logo.history_title')"
                        :from-label="__('player.edit.logo.history_from')"
                        :until-label="__('player.edit.logo.history_until')"
                        :save-label="__('team.roster.save')"
                        :add-label="__('player.edit.logo.history_add')"
                        :remove-label="__('team.roster.remove')"
                        :remove-confirm-title="__('team.roster.remove')"
                        :remove-confirm-body="fn ($logo) => __('player.edit.logo.history_remove_confirm')"
                        :empty-label="__('player.edit.logo.history_empty')"
                    />
                </div>

                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.edit.profile.title') }}</h2>

                    <form method="POST" action="{{ route('admin.players.update', $player) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @include('player._profile-form', ['player' => $player])

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('player.edit.profile.submit') }}
                        </button>
                    </form>
                </div>
            @endcan

            @canany(['players.edit', 'players.identifiers.manage'])
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.players.identifiers.title') }}</h2>

                    @can('players.identifiers.manage')
                        <form method="POST" action="{{ route('admin.players.identifiers.update', $player) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="val_id" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                    {{ __('admin.players.identifiers.val_id') }}
                                </label>
                                <input id="val_id" type="text" name="val_id" value="{{ old('val_id', $player->val_id) }}"
                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                @error('val_id')
                                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="discord_id" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                    {{ __('admin.players.identifiers.discord_id') }}
                                </label>
                                <input id="discord_id" type="text" name="discord_id" value="{{ old('discord_id', $player->discord_id) }}"
                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                @error('discord_id')
                                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                {{ __('admin.players.identifiers.submit') }}
                            </button>
                        </form>
                    @endcan

                    @can('players.edit')
                        <div @class(['flex gap-2', 'pt-4 border-t border-white/10' => auth()->user()->can('players.identifiers.manage')])>
                            <form method="POST" action="{{ route('admin.players.val-id.destroy', $player) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="__('admin.players.identifiers.reset_val_id')"
                                    :body="__('admin.players.identifiers.reset_confirm')"
                                    :trigger-label="__('admin.players.identifiers.reset_val_id')"
                                    :submit-label="__('admin.players.identifiers.reset_val_id')"
                                    trigger-class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                            <form method="POST" action="{{ route('admin.players.discord-id.destroy', $player) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="__('admin.players.identifiers.reset_discord_id')"
                                    :body="__('admin.players.identifiers.reset_confirm')"
                                    :trigger-label="__('admin.players.identifiers.reset_discord_id')"
                                    :submit-label="__('admin.players.identifiers.reset_discord_id')"
                                    trigger-class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        </div>
                    @endcan
                </div>
            @endcanany

            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.players.linked_user.title') }}</h2>
                <p class="text-xs text-gray-500">{{ __('admin.players.linked_user.body') }}</p>

                @if ($player->user)
                    <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                        <div>
                            <p class="text-sm text-white font-semibold">
                                {{ $player->user->name }}
                                @if ($player->user->username)
                                    <span class="text-gray-500 font-normal">{{ '@'.$player->user->username }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">{{ $player->user->email }}</p>
                        </div>
                        @can('players.edit')
                            <form method="POST" action="{{ route('admin.players.user.destroy', $player) }}">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="__('admin.players.linked_user.remove')"
                                    :body="__('admin.players.linked_user.remove_confirm', ['user' => $player->user->name, 'player' => $player->handle])"
                                    :trigger-label="__('admin.players.linked_user.remove')"
                                    :submit-label="__('admin.players.linked_user.remove')"
                                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        @endcan
                    </div>
                @else
                    <p class="text-xs text-gray-500">{{ __('admin.players.linked_user.no_user') }}</p>

                    @can('players.edit')
                        <x-modal :title="__('admin.players.linked_user.add')" :open-by-default="$userSearch !== ''">
                            <x-slot:trigger>
                                <button type="button"
                                        class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                    {{ __('admin.players.linked_user.add') }}
                                </button>
                            </x-slot:trigger>

                            <form method="GET" action="{{ route('admin.players.show', $player) }}" class="flex gap-2">
                                <input type="text" name="user_q" value="{{ $userSearch }}" placeholder="{{ __('admin.players.linked_user.search_placeholder') }}"
                                       class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                <button type="submit"
                                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                    {{ __('admin.players.linked_user.search_submit') }}
                                </button>
                            </form>

                            @if ($userSearch)
                                <div class="space-y-2 pt-4">
                                    @forelse ($userSearchResults as $found)
                                        <form method="POST" action="{{ route('admin.players.user.update', $player) }}" class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="user_id" value="{{ $found->id }}">
                                            <div>
                                                <p class="text-xs text-white font-semibold">
                                                    {{ $found->name }}
                                                    @if ($found->username)
                                                        <span class="text-gray-500 font-normal">{{ '@'.$found->username }}</span>
                                                    @endif
                                                </p>
                                                <p class="text-[10px] text-gray-500">{{ $found->email }}</p>
                                            </div>
                                            <button type="submit"
                                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                                {{ __('admin.players.linked_user.assign') }}
                                            </button>
                                        </form>
                                    @empty
                                        <p class="text-xs text-gray-500">{{ __('admin.players.linked_user.search_empty') }}</p>
                                    @endforelse
                                </div>
                            @endif
                        </x-modal>
                        @error('user_id')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    @endcan
                @endif
            </div>
        </div>
    </div>
@endsection
