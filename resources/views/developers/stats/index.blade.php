{{--
    GC-Stats — Developers: API usage statistics

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('developers.layout')

@section('title', __('developers.dashboard.stats.title'))

@section('content')
    @php
        $statusClass = function (float $value, float $warnAt, float $criticalAt) {
            if ($value >= $criticalAt) {
                return 'bg-red-500/10 text-red-400 border-red-500/30';
            }
            if ($value >= $warnAt) {
                return 'bg-amber-500/10 text-amber-400 border-amber-500/30';
            }

            return 'bg-green-500/10 text-green-400 border-green-500/30';
        };
    @endphp

    <div class="flex items-center justify-end mb-4">
        <label class="flex items-center gap-2">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('developers.dashboard.filter.key') }}</span>
            <x-styled-select navigate class="w-56" :selected="route('developers.stats.index', $key)"
                :options="$keys->mapWithKeys(fn ($option) => [route('developers.stats.index', $option) => $option->client_name])" />
        </label>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-bg-card border border-white/5 rounded-xl p-5">
            <p class="text-2xl font-black tracking-tight text-white leading-none tabular-nums">{{ number_format($volume['24h']) }}</p>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mt-1.5">{{ __('developers.dashboard.stats.requests_24h') }}</p>
        </div>
        <div class="bg-bg-card border border-white/5 rounded-xl p-5">
            <p class="text-2xl font-black tracking-tight text-white leading-none tabular-nums">{{ number_format($volume['7d']) }}</p>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mt-1.5">{{ __('developers.dashboard.stats.requests_7d') }}</p>
        </div>
        <div class="bg-bg-card border border-white/5 rounded-xl p-5">
            <p class="text-2xl font-black tracking-tight text-white leading-none tabular-nums">{{ number_format($volume['30d']) }}</p>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mt-1.5">{{ __('developers.dashboard.stats.requests_30d') }}</p>
        </div>
        <div class="bg-bg-card border rounded-xl p-5 {{ $statusClass($errorRate, 1, 5) }}">
            <p class="text-2xl font-black tracking-tight leading-none tabular-nums">{{ $errorRate }}%</p>
            <p class="text-[10px] font-black uppercase tracking-widest opacity-70 mt-1.5">{{ __('developers.dashboard.stats.error_rate') }}</p>
        </div>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl p-4 mb-6">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-4">{{ __('developers.dashboard.stats.response_time_title') }}</p>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="rounded-lg border p-3 {{ $statusClass($latency['min'], 200, 500) }}">
                <p class="text-lg font-black tracking-tight leading-none tabular-nums">{{ $latency['min'] }} ms</p>
                <p class="text-[9px] font-black uppercase tracking-widest opacity-70 mt-1">{{ __('developers.dashboard.stats.min') }}</p>
            </div>
            <div class="rounded-lg border p-3 {{ $statusClass($latency['p50'], 200, 500) }}">
                <p class="text-lg font-black tracking-tight leading-none tabular-nums">{{ $latency['p50'] }} ms</p>
                <p class="text-[9px] font-black uppercase tracking-widest opacity-70 mt-1">{{ __('developers.dashboard.stats.p50') }}</p>
            </div>
            <div class="rounded-lg border p-3 {{ $statusClass($latency['p95'], 200, 500) }}">
                <p class="text-lg font-black tracking-tight leading-none tabular-nums">{{ $latency['p95'] }} ms</p>
                <p class="text-[9px] font-black uppercase tracking-widest opacity-70 mt-1">{{ __('developers.dashboard.stats.p95') }}</p>
            </div>
            <div class="rounded-lg border p-3 {{ $statusClass($latency['p99'], 200, 500) }}">
                <p class="text-lg font-black tracking-tight leading-none tabular-nums">{{ $latency['p99'] }} ms</p>
                <p class="text-[9px] font-black uppercase tracking-widest opacity-70 mt-1">{{ __('developers.dashboard.stats.p99') }}</p>
            </div>
            <div class="rounded-lg border p-3 {{ $statusClass($latency['max'], 200, 500) }}">
                <p class="text-lg font-black tracking-tight leading-none tabular-nums">{{ $latency['max'] }} ms</p>
                <p class="text-[9px] font-black uppercase tracking-widest opacity-70 mt-1">{{ __('developers.dashboard.stats.max') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl p-4 mb-6">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-4">{{ __('developers.dashboard.stats.chart_title') }}</p>
        <div class="h-64">
            <canvas id="developers-stats-chart"></canvas>
        </div>
        <script type="application/json" id="developers-stats-data">{!! json_encode([
            'labels' => $daily['labels'],
            'requests' => $daily['requests'],
            'errors' => $daily['errors'],
            'labelRequests' => __('developers.dashboard.stats.chart_requests'),
            'labelErrors' => __('developers.dashboard.stats.chart_errors'),
        ]) !!}</script>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <div class="px-4 py-3 border-b border-b-white/10">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('developers.dashboard.stats.top_endpoints_title') }}</p>
        </div>
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-b-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['endpoint', 'developers.dashboard.stats.endpoint'], ['requests', 'developers.dashboard.stats.requests'], ['avg_duration', 'developers.dashboard.stats.avg_response_time'], ['error_rate', 'developers.dashboard.stats.error_rate_col']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($endpoints as $row)
                    <tr class="border-b border-b-white/10 last:border-b-0">
                        <td class="px-4 py-3 text-white font-mono text-xs">{{ $row->endpoint }}</td>
                        <td class="px-4 py-3 text-gray-400 tabular-nums">{{ number_format($row->requests) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-[10px] font-bold tabular-nums rounded-lg border {{ $statusClass((float) $row->avg_duration, 200, 500) }}">
                                {{ round($row->avg_duration) }} ms
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-[10px] font-bold tabular-nums rounded-lg border {{ $statusClass((float) $row->error_rate, 1, 5) }}">
                                {{ $row->error_rate }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('developers.dashboard.stats.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $endpoints->links() }}

    @push('scripts')
        @vite('resources/js/developers/stats/index.js')
    @endpush
@endsection
