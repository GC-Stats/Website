{{--
    GC-Stats — Developers: request history

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('developers.layout')

@section('title', __('developers.dashboard.requests.title'))

@section('content')
    <div class="flex items-center justify-end mb-4">
        <label class="flex items-center gap-2">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('developers.dashboard.filter.key') }}</span>
            <select onchange="window.location = this.value"
                    class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                @foreach ($keys as $option)
                    <option value="{{ route('developers.requests.index', $option) }}" @selected($key->id === $option->id)>{{ $option->client_name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <form method="GET" action="{{ route('developers.requests.index', $key) }}" class="flex flex-wrap items-end gap-2 mb-6">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('developers.dashboard.requests.endpoint') }}</span>
            <select name="endpoint" class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('developers.dashboard.requests.filter.all_endpoints') }}</option>
                @foreach ($endpoints as $option)
                    <option value="{{ $option }}" @selected($endpoint === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('developers.dashboard.requests.status') }}</span>
            <select name="status" class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('developers.dashboard.requests.filter.all_statuses') }}</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('developers.dashboard.requests.filter.from') }}</span>
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('developers.dashboard.requests.filter.to') }}</span>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>

        <button type="submit"
                class="h-[42px] font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            {{ __('developers.dashboard.requests.filter.submit') }}
        </button>

        @if ($endpoint || $status || $dateFrom || $dateTo)
            <a href="{{ route('developers.requests.index', ['key' => $key->id, 'sort' => $sort, 'direction' => $direction]) }}"
               class="h-[42px] inline-flex items-center font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('developers.dashboard.requests.filter.reset') }}
            </a>
        @endif
    </form>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['when', 'developers.dashboard.requests.when'], ['endpoint', 'developers.dashboard.requests.endpoint'], ['status', 'developers.dashboard.requests.status'], ['duration', 'developers.dashboard.requests.duration']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3 text-white font-mono text-xs">
                            <span class="text-gray-500">{{ $log->method }}</span> {{ $log->endpoint }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg {{ $log->status_code < 400 ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-red-500/10 text-red-400 border border-red-500/30' }}">
                                {{ $log->status_code }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 tabular-nums">{{ $log->duration_ms }} ms</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('developers.dashboard.requests.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
@endsection
