{{--
    GC-Stats — Developers: API keys

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('developers.layout')

@section('title', __('developers.dashboard.api_keys.title'))

@section('content')
    @if (session('reveal_url'))
        <div x-data="{ copied: false }" class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('developers.dashboard.api_keys.reveal_banner.title') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('developers.dashboard.api_keys.reveal_banner.body') }}</p>
            </div>
            <button type="button"
                    @click="navigator.clipboard.writeText('{{ session('reveal_url') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] shrink-0">
                <span x-show="!copied">{{ __('developers.dashboard.api_keys.reveal_banner.copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('developers.dashboard.api_keys.reveal_banner.copied') }}</span>
            </button>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <form method="GET" class="flex-1 min-w-[200px] max-w-sm">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('developers.dashboard.api_keys.search_placeholder') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </form>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['client_name', 'developers.dashboard.api_keys.client_name'], ['rate_limit', 'developers.dashboard.api_keys.rate_limit'], ['status', 'developers.dashboard.api_keys.status']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($keys as $key)
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $key->client_name }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $key->rate_limit }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg {{ $key->is_active ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30' }}">
                                {{ $key->is_active ? __('developers.dashboard.api_keys.active') : __('developers.dashboard.api_keys.inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('developers.api-keys.regenerate', $key) }}">
                                @csrf
                                @method('PATCH')
                                <x-confirm-modal
                                    :title="__('developers.dashboard.api_keys.regenerate')"
                                    :body="__('developers.dashboard.api_keys.regenerate_confirm')"
                                    :trigger-label="__('developers.dashboard.api_keys.regenerate')"
                                    :submit-label="__('developers.dashboard.api_keys.regenerate')"
                                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                />
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('developers.dashboard.api_keys.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $keys->links() }}
@endsection
