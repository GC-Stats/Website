{{--
    GC-Stats — Admin: change requests queue

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.change_requests.title'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-2">
            @foreach ($statuses as $option)
                <a href="{{ request()->fullUrlWithQuery(['status' => $option, 'page' => null]) }}"
                   class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ $status === $option ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                    {{ __('admin.change_requests.status.'.$option) }}
                </a>
            @endforeach
        </div>

        @can('change-requests.create')
            <a href="{{ route('admin.change-requests.create') }}"
               class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105">
                {{ __('admin.change_requests.create.new') }}
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('admin.change-requests.index') }}" class="flex flex-wrap items-end gap-2 mb-6">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.change_requests.filter.requested_by') }}</span>
            <input type="text" name="requested_by_name" value="{{ $requestedByName }}" placeholder="{{ __('admin.change_requests.filter.requested_by_placeholder') }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.change_requests.filter.subject_type') }}</span>
            <x-styled-select name="subject_type" :selected="$subjectType" class="w-40"
                :options="collect(['' => __('admin.change_requests.filter.all_subject_types')])->merge(collect($subjectTypes)->mapWithKeys(fn ($type) => [$type => __('admin.change_requests.create.subject_type.'.$type)]))" />
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.change_requests.filter.subject') }}</span>
            <input type="text" name="subject_query" value="{{ $subjectQuery }}" placeholder="{{ __('admin.change_requests.filter.subject_placeholder') }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.change_requests.filter.field') }}</span>
            <x-styled-select name="field" :selected="$field" class="w-44"
                :options="collect(['' => __('admin.change_requests.filter.all_fields')])->merge(collect($fieldOptions)->mapWithKeys(fn ($option) => [$option => $option]))" />
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.change_requests.filter.from') }}</span>
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.change_requests.filter.to') }}</span>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>

        <button type="submit"
                class="h-[42px] font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            {{ __('admin.change_requests.filter.submit') }}
        </button>

        @if ($requestedByName || $subjectType || $subjectQuery || $field || $dateFrom || $dateTo)
            <a href="{{ route('admin.change-requests.index', ['status' => $status]) }}"
               class="h-[42px] inline-flex items-center font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.change_requests.filter.reset') }}
            </a>
        @endif
    </form>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.change_requests.subject') }}</th>
                    <th class="px-4 py-3">{{ __('admin.change_requests.field') }}</th>
                    <x-admin.sortable-th col="requested_by" :sort="$sort" :direction="$direction">{{ __('admin.change_requests.requested_by') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="submitted_at" :sort="$sort" :direction="$direction">{{ __('admin.change_requests.submitted_at') }}</x-admin.sortable-th>
                    <th class="px-4 py-3">{{ __('admin.change_requests.status_label') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $changeRequest)
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">
                            {{ $changeRequest->subject?->name ?? $changeRequest->subject?->handle ?? '#'.$changeRequest->subject_id }}
                            <span class="text-gray-500 font-normal block text-xs">{{ ucfirst($changeRequest->subject_type) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            {{ $changeRequest->items->pluck('field')->implode(', ') }}
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            @if ($changeRequest->isSystemGenerated())
                                <span class="text-gc-yellow">{{ __('admin.change_requests.system_generated') }}</span>
                            @else
                                {{ $changeRequest->requestedBy?->name ?? '—' }}
                                @if ($changeRequest->requestedBy?->username)
                                    <span class="text-gray-500">{{ '@'.$changeRequest->requestedBy->username }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $changeRequest->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <x-change-request-status-badge :status="$changeRequest->status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.change-requests.show', $changeRequest) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.change_requests.view') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.change_requests.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $requests->links() }}
@endsection
