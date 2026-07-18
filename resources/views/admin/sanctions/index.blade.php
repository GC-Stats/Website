{{--
    GC-Stats — Admin: sanctions

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.sanctions.title'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-2">
            <a href="{{ route('admin.sanctions.index') }}"
               class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all {{ ! $showAll ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                {{ __('admin.sanctions.active_only') }}
            </a>
            <a href="{{ route('admin.sanctions.index', ['all' => 1]) }}"
               class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all {{ $showAll ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                {{ __('admin.sanctions.show_all') }}
            </a>
        </div>

        @can('sanctions.create')
            <x-modal :title="__('admin.sanctions.issue.title')">
                <x-slot:trigger>
                    <button type="button"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                        {{ __('admin.sanctions.issue.title') }}
                    </button>
                </x-slot:trigger>
                @include('admin.sanctions._form')
            </x-modal>
        @endcan
    </div>

    <div class="bg-bg-card border border-border-subtle rounded-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-border-subtle text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.sanctions.user') }}</th>
                    <th class="px-4 py-3">{{ __('admin.sanctions.type_column') }}</th>
                    <th class="px-4 py-3">{{ __('admin.sanctions.reason') }}</th>
                    <th class="px-4 py-3">{{ __('admin.sanctions.ends_at') }}</th>
                    <th class="px-4 py-3">{{ __('admin.sanctions.issued_by') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sanctions as $sanction)
                    <tr class="border-b border-border-subtle last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $sanction->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ __('admin.sanctions.type.'.$sanction->type) }}</td>
                        <td class="px-4 py-3 text-gray-400 max-w-xs truncate" title="{{ $sanction->reason }}">{{ $sanction->reason }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $sanction->ends_at?->format('Y-m-d H:i') ?? __('admin.sanctions.permanent') }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $sanction->issuedBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                @can('sanctions.revoke')
                                    @if ($sanction->isActive())
                                        <form method="POST" action="{{ route('admin.sanctions.destroy', $sanction) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-confirm-modal
                                                :title="__('admin.sanctions.revoke')"
                                                :body="__('admin.sanctions.revoke_confirm')"
                                                :trigger-label="__('admin.sanctions.revoke')"
                                                :submit-label="__('admin.sanctions.revoke')"
                                                trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                            />
                                        </form>
                                    @endif
                                @endcan
                                @can('sanctions.delete')
                                    <form method="POST" action="{{ route('admin.sanctions.force-destroy', $sanction) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-confirm-modal
                                            :title="__('admin.sanctions.delete')"
                                            :body="__('admin.sanctions.delete_confirm')"
                                            :trigger-label="__('admin.sanctions.delete')"
                                            :submit-label="__('admin.sanctions.delete')"
                                            trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                        />
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.sanctions.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $sanctions->links() }}
@endsection
