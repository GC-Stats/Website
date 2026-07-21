{{--
    GC-Stats — Admin: API keys

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.api_keys.title'))

@section('content')
    @if (session('reveal_url'))
        <div x-data="{ copied: false }" class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.api_keys.reveal_banner.title') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('admin.api_keys.reveal_banner.body') }}</p>
            </div>
            <button type="button"
                    @click="navigator.clipboard.writeText('{{ session('reveal_url') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] shrink-0">
                <span x-show="!copied">{{ __('admin.api_keys.reveal_banner.copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('admin.api_keys.reveal_banner.copied') }}</span>
            </button>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <form method="GET" class="flex-1 min-w-[200px] max-w-sm">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.api_keys.search_placeholder') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </form>

        @can('api-keys.manage')
            <x-modal :title="__('admin.api_keys.create.title')">
                <x-slot:trigger>
                    <button type="button"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.api_keys.create.title') }}
                    </button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('admin.api-keys.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.api_keys.create.client_name_label') }}
                        </label>
                        <input type="text" name="client_name" required minlength="3" maxlength="50"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('client_name')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.api_keys.create.rate_limit_label') }}
                        </label>
                        <input type="number" name="rate_limit" required min="1" value="60"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        @error('rate_limit')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.api_keys.create.submit') }}
                    </button>
                </form>
            </x-modal>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto"
         x-data="GCS.sortableTable()">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['client_name', 'admin.api_keys.client_name'], ['rate_limit', 'admin.api_keys.rate_limit'], ['status', 'admin.api_keys.status']] as [$col, $label])
                        <th class="px-4 py-3" @click="sortBy('{{ $col }}')">
                            <span class="group inline-flex items-center gap-1 hover:text-white transition cursor-pointer select-none">
                                {{ __($label) }}
                                @include('admin.partials.sort-arrows', ['col' => $col])
                            </span>
                        </th>
                    @endforeach
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody x-ref="tbody">
                @forelse ($keys as $key)
                    <tr data-row data-client_name="{{ $key->client_name }}" data-rate_limit="{{ $key->rate_limit }}" data-status="{{ $key->is_active ? 1 : 0 }}"
                        class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $key->client_name }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $key->rate_limit }}</td>
                        <td class="px-4 py-3">
                            @can('api-keys.manage')
                                <form method="POST" action="{{ route('admin.api-keys.toggle', $key) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg transition {{ $key->is_active ? 'bg-green-500/10 text-green-400 border border-green-500/30 hover:bg-green-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30 hover:bg-gray-500/20' }}">
                                        {{ $key->is_active ? __('admin.api_keys.active') : __('admin.api_keys.inactive') }}
                                    </button>
                                </form>
                            @else
                                <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg {{ $key->is_active ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30' }}">
                                    {{ $key->is_active ? __('admin.api_keys.active') : __('admin.api_keys.inactive') }}
                                </span>
                            @endcan
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('api-keys.manage')
                                <div class="flex justify-end gap-2">
                                    <x-modal :title="__('admin.api_keys.edit_modal.title')" max-width="max-w-sm">
                                        <x-slot:trigger>
                                            <button type="button"
                                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                                {{ __('admin.api_keys.edit') }}
                                            </button>
                                        </x-slot:trigger>

                                        <form method="POST" action="{{ route('admin.api-keys.update', $key) }}" class="space-y-4">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                                    {{ __('admin.api_keys.create.client_name_label') }}
                                                </label>
                                                <input type="text" name="client_name" required minlength="3" maxlength="50" value="{{ $key->client_name }}"
                                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                                    {{ __('admin.api_keys.create.rate_limit_label') }}
                                                </label>
                                                <input type="number" name="rate_limit" required min="1" value="{{ $key->rate_limit }}"
                                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                            </div>
                                            <button type="submit"
                                                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                                {{ __('admin.api_keys.edit_modal.submit') }}
                                            </button>
                                        </form>
                                    </x-modal>

                                    <form method="POST" action="{{ route('admin.api-keys.regenerate', $key) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-confirm-modal
                                            :title="__('admin.api_keys.regenerate')"
                                            :body="__('admin.api_keys.regenerate_confirm')"
                                            :trigger-label="__('admin.api_keys.regenerate')"
                                            :submit-label="__('admin.api_keys.regenerate')"
                                            trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                        />
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.api_keys.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $keys->links() }}
@endsection
