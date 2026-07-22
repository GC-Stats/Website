{{--
    GC-Stats — Admin: point types list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.point_types.title'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ __('admin.point_types.title') }}</h1>

        @can('tournaments.edit')
            <a href="{{ route('admin.point-types.create') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                + {{ __('admin.point_types.create.title') }}
            </a>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.point_types.name') }}</th>
                    <th class="px-4 py-3">{{ __('admin.point_types.label') }}</th>
                    <th class="px-4 py-3">{{ __('admin.point_types.start_date') }}</th>
                    <th class="px-4 py-3">{{ __('admin.point_types.end_date') }}</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pointTypes as $pointType)
                    <tr class="border-b border-b-white/10 last:border-b-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $pointType->name }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $pointType->label }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $pointType->start_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $pointType->end_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right flex justify-end gap-2">
                            <a href="{{ route('admin.point-types.edit', $pointType) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.point_types.manage') }}
                            </a>
                            @can('tournaments.edit')
                                <form method="POST" action="{{ route('admin.point-types.destroy', $pointType) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.point_types.delete.title')"
                                        :body="__('admin.point_types.delete.confirm_body', ['name' => $pointType->name, 'label' => $pointType->label])"
                                        :trigger-label="__('admin.point_types.delete.trigger')"
                                        :submit-label="__('admin.point_types.delete.trigger')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.point_types.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
