{{--
    GC-Stats — Admin: reports queue

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.reports.title'))

@section('content')
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ($statuses as $option)
            <a href="{{ route('admin.reports.index', ['status' => $option, 'sort' => $sort, 'direction' => $direction]) }}"
               class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ $status === $option ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                {{ __('admin.reports.status.'.$option) }}
            </a>
        @endforeach
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['reported_user', 'admin.reports.reported_user'], ['reporter', 'admin.reports.reporter'], ['category', 'admin.reports.category_column'], ['team', 'admin.reports.team'], ['submitted_at', 'admin.reports.submitted_at']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">
                            {{ $report->reportedUser?->name ?? '—' }}
                            @if ($report->reportedUser?->username)
                                <span class="text-gray-500 font-normal">{{ '@'.$report->reportedUser->username }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">
                            {{ $report->reporter?->name ?? '—' }}
                            @if ($report->reporter?->username)
                                <span class="text-gray-500">{{ '@'.$report->reporter->username }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ __('admin.reports.category.'.$report->category) }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $report->team?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $report->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.reports.show', $report) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.reports.view') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.reports.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
@endsection
