{{--
    GC-Stats — Admin: analytics

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.analytics.title'))

@section('content')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ($regions as $region)
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.analytics.region.'.$region) }}</p>
                <p class="text-2xl font-black text-white">{{ number_format($dailyAverages[$region]) }}</p>
                <p class="text-[10px] text-gray-500 mt-1">{{ __('admin.analytics.daily_average') }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl p-4 mb-6">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-4">{{ __('admin.analytics.hourly_chart_title') }}</p>
        <div class="h-64">
            <canvas id="admin-analytics-hourly-chart"></canvas>
        </div>
        <script type="application/json" id="admin-analytics-hourly-data">{!! json_encode($hourly) !!}</script>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <div class="px-4 py-3 border-b border-b-white/10">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('admin.analytics.top_pages_title') }}</p>
        </div>
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-b-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['page', 'admin.analytics.page'], ['views', 'admin.analytics.views']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($topPages as $page)
                    <tr class="border-b border-b-white/10 last:border-b-0">
                        <td class="px-4 py-3 text-white font-mono text-xs">{{ $page->uri }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ number_format($page->total_count) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.analytics.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $topPages->links() }}

    @push('scripts')
        @vite('resources/js/admin/analytics/index.js')
    @endpush
@endsection
