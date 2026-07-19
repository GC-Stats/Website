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
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ __('admin.players.public_page') }}
            </a>
            @can('players.merge')
                <a href="{{ route('admin.players.merge.show', $player) }}"
                   class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
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
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3 mb-6">
            {{ __('admin.status.'.session('status')) }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3 mb-6">
            {{ __('admin.status.'.session('error')) }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @can('players.edit')
                {{-- Logo --}}
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
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

                {{-- Profile --}}
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('player.edit.profile.title') }}</h2>

                    <form method="POST" action="{{ route('admin.players.update', $player) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @include('player._profile-form', ['player' => $player])

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('player.edit.profile.submit') }}
                        </button>
                    </form>
                </div>
            @endcan

            @canany(['players.edit', 'players.identifiers.manage'])
                {{-- Identifiers --}}
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
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
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                @error('val_id')
                                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="discord_id" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                    {{ __('admin.players.identifiers.discord_id') }}
                                </label>
                                <input id="discord_id" type="text" name="discord_id" value="{{ old('discord_id', $player->discord_id) }}"
                                       class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                @error('discord_id')
                                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                                {{ __('admin.players.identifiers.submit') }}
                            </button>
                        </form>
                    @endcan

                    @can('players.edit')
                        <div @class(['flex gap-2', 'pt-4 border-t border-border-subtle' => auth()->user()->can('players.identifiers.manage')])>
                            <form method="POST" action="{{ route('admin.players.val-id.destroy', $player) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <x-confirm-modal
                                    :title="__('admin.players.identifiers.reset_val_id')"
                                    :body="__('admin.players.identifiers.reset_confirm')"
                                    :trigger-label="__('admin.players.identifiers.reset_val_id')"
                                    :submit-label="__('admin.players.identifiers.reset_val_id')"
                                    trigger-class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
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
                                    trigger-class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        </div>
                    @endcan
                </div>
            @endcanany
        </div>
    </div>
@endsection
