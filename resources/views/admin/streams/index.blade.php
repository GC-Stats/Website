{{--
    GC-Stats — Admin: stream channels list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.streams.title'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ __('admin.streams.title') }}</h1>

        @can('streams.action.create')
            <a href="{{ route('admin.streams.create') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                + {{ __('admin.streams.create.title') }}
            </a>
        @endcan
    </div>

    <form method="GET" class="mb-6 flex flex-wrap gap-3">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.streams.search_placeholder') }}"
               class="flex-1 min-w-[200px] max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

        <select name="platform" onchange="this.form.submit()"
                class="bg-white/5 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            <option value="">{{ __('admin.streams.all_platforms') }}</option>
            @foreach ($platforms as $platformOption)
                <option value="{{ $platformOption }}" @selected($platform === $platformOption)>{{ ucfirst($platformOption) }}</option>
            @endforeach
        </select>

        @if ($search || $platform)
            <a href="{{ route('admin.streams.index') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-gray-400 hover:text-white">
                {{ __('admin.streams.clear_filters') }}
            </a>
        @endif
    </form>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3"></th>
                    <x-admin.sortable-th col="name" :sort="$sort" :direction="$direction">{{ __('admin.streams.name') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="platform" :sort="$sort" :direction="$direction">{{ __('admin.streams.platform') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="language_code" :sort="$sort" :direction="$direction">{{ __('admin.streams.language') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="publisher" :sort="$sort" :direction="$direction">{{ __('admin.streams.publisher') }}</x-admin.sortable-th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($channels as $channel)
                    <tr class="border-b border-b-white/10 last:border-b-0">
                        <td class="px-4 py-3">
                            <span class="w-8 h-8 flex items-center justify-center bg-white/5 rounded-lg">
                                @svg($channel->icon(), 'w-4 h-4 text-white', ['aria-hidden' => 'true'])
                            </span>
                        </td>
                        <td class="px-4 py-3 text-white font-semibold">
                            <a href="{{ $channel->url }}" target="_blank" rel="noopener noreferrer" class="hover:text-gc-yellow transition">
                                {{ $channel->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-300">{{ ucfirst($channel->platform) }}</td>
                        <td class="px-4 py-3">
                            <span class="fi fi-{{ $channel->language_code === \App\Support\Countries::INTERNATIONAL ? 'un' : $channel->language_code }} shadow-sm"></span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-300">{{ $channel->publisher?->name ?? __('admin.streams.admin_channel') }}</td>
                        <td class="px-4 py-3 text-right flex justify-end gap-2">
                            @if ($editablePublisherIds === null || $editablePublisherIds->contains($channel->publisher_id))
                                <a href="{{ route('admin.streams.edit', $channel) }}"
                                   class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                    {{ __('admin.streams.manage') }}
                                </a>
                            @endif
                            @if ($deletablePublisherIds === null || $deletablePublisherIds->contains($channel->publisher_id))
                                <form method="POST" action="{{ route('admin.streams.destroy', $channel) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.streams.delete.title')"
                                        :body="__('admin.streams.delete.confirm_body', ['name' => $channel->name])"
                                        :trigger-label="__('admin.streams.delete.trigger')"
                                        :submit-label="__('admin.streams.delete.trigger')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.streams.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $channels->links() }}
@endsection
