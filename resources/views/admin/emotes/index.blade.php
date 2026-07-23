{{--
    GC-Stats — Admin: emotes list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.emotes.title'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ __('admin.emotes.title') }}</h1>

        @can('emotes.create')
            <a href="{{ route('admin.emotes.create') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                + {{ __('admin.emotes.create.title') }}
            </a>
        @endcan
    </div>

    <form method="GET" class="mb-6 flex flex-wrap gap-3">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.emotes.search_placeholder') }}"
               class="flex-1 min-w-[200px] max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

        <select name="status" onchange="this.form.submit()"
                class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            <option value="">{{ __('admin.emotes.all_statuses') }}</option>
            <option value="active" @selected($statusFilter === 'active')>{{ __('admin.emotes.active') }}</option>
            <option value="inactive" @selected($statusFilter === 'inactive')>{{ __('admin.emotes.inactive') }}</option>
        </select>

        <select name="source" onchange="this.form.submit()"
                class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            <option value="">{{ __('admin.emotes.all_sources') }}</option>
            @foreach ($sources as $sourceOption)
                <option value="{{ $sourceOption }}" @selected($sourceFilter === $sourceOption)>
                    {{ $sourceOption === 'custom' ? __('admin.emotes.source_custom') : $sourceOption }}
                </option>
            @endforeach
        </select>

        @if ($search || $statusFilter || $sourceFilter)
            <a href="{{ route('admin.emotes.index') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-gray-400 hover:text-white">
                {{ __('admin.emotes.clear_filters') }}
            </a>
        @endif
    </form>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3"></th>
                    <x-admin.sortable-th col="name" :sort="$sort" :direction="$direction">{{ __('admin.emotes.name') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="status" :sort="$sort" :direction="$direction">{{ __('admin.emotes.status') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="source" :sort="$sort" :direction="$direction">{{ __('admin.emotes.source') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="created" :sort="$sort" :direction="$direction">{{ __('admin.emotes.created') }}</x-admin.sortable-th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($emotes as $emote)
                    <tr class="border-b border-b-white/10 last:border-b-0">
                        <td class="px-4 py-3">
                            <img src="{{ $emote->image_url }}" alt="{{ $emote->name }}" class="w-8 h-8 object-contain">
                        </td>
                        <td class="px-4 py-3 text-white font-semibold">{{ $emote->name }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs {{ $emote->is_active ? 'text-green-400' : 'text-gray-600' }}">
                                {{ $emote->is_active ? __('admin.emotes.active') : __('admin.emotes.inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-300">
                            {{ $emote->source === 'custom' ? __('admin.emotes.source_custom') : $emote->source }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $emote->created_at?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right flex justify-end gap-2">
                            @can('emotes.edit')
                                <a href="{{ route('admin.emotes.edit', $emote) }}"
                                   class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                    {{ __('admin.emotes.manage') }}
                                </a>
                            @endcan
                            @can('emotes.delete')
                                <form method="POST" action="{{ route('admin.emotes.destroy', $emote) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.emotes.delete.title')"
                                        :body="__('admin.emotes.delete.confirm_body', ['name' => $emote->name])"
                                        :trigger-label="__('admin.emotes.delete.trigger')"
                                        :submit-label="__('admin.emotes.delete.trigger')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.emotes.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $emotes->links() }}
@endsection
