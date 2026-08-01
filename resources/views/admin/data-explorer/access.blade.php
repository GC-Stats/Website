{{--
    GC-Stats — Admin: Data Explorer access

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.data_explorer.access.title'))

@section('content')
    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <form method="GET" class="flex-1 min-w-[200px] max-w-2xl flex gap-3">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.data_explorer.access.search_placeholder') }}"
                   class="flex-1 min-w-[200px] max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

            <x-styled-select name="status" :selected="$statusFilter" autosubmit class="w-44"
                :options="[
                    '' => __('admin.data_explorer.access.all_statuses'),
                    'enabled' => __('admin.data_explorer.access.enabled'),
                    'disabled' => __('admin.data_explorer.access.disabled'),
                ]" />

            @if ($search || $statusFilter)
                <a href="{{ route('admin.data-explorer.access') }}"
                   class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-gray-400 hover:text-white">
                    {{ __('admin.data_explorer.access.clear_filters') }}
                </a>
            @endif
        </form>

        @can('data-explorer.view')
            <a href="{{ route('admin.data-explorer.usage') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.data_explorer.access.view_usage') }}
            </a>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.data_explorer.access.user') }}</th>
                    <th class="px-4 py-3">{{ __('admin.data_explorer.access.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <x-user-avatar :user="$user" class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 text-[10px]" />
                                <div class="min-w-0">
                                    <p class="text-white font-semibold truncate">{{ $user->name }}</p>
                                    @if ($user->username)
                                        <p class="text-xs text-gray-500 truncate">{{ '@'.$user->username }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @can('data-explorer.manage')
                                <form method="POST" action="{{ route('admin.data-explorer.access.toggle', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg transition {{ $user->data_explorer_enabled ? 'bg-green-500/10 text-green-400 border border-green-500/30 hover:bg-green-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30 hover:bg-gray-500/20' }}">
                                        {{ $user->data_explorer_enabled ? __('admin.data_explorer.access.enabled') : __('admin.data_explorer.access.disabled') }}
                                    </button>
                                </form>
                            @else
                                <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg {{ $user->data_explorer_enabled ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30' }}">
                                    {{ $user->data_explorer_enabled ? __('admin.data_explorer.access.enabled') : __('admin.data_explorer.access.disabled') }}
                                </span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.data_explorer.access.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
