{{--
    GC-Stats — Admin: Data Explorer global usage

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.data_explorer.usage.title'))

@section('content')
    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl p-6 mb-6">
        <p class="text-[10px] font-black uppercase tracking-widest text-gc-yellow mb-3">{{ __('admin.data_explorer.usage.total_title') }}</p>
        <p class="text-3xl font-black text-white">{{ number_format($total_used) }} <span class="text-gray-500 text-lg font-semibold">/ {{ number_format($total_quota) }}</span></p>

        <div class="mt-4 h-2 rounded-full bg-white/5 overflow-hidden">
            <div class="h-full bg-gc-yellow" style="width: {{ $total_quota > 0 ? min(100, round($total_used / $total_quota * 100)) : 0 }}%"></div>
        </div>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.data_explorer.usage.user') }}</th>
                    <th class="px-4 py-3">{{ __('admin.data_explorer.usage.source') }}</th>
                    <th class="px-4 py-3">{{ __('admin.data_explorer.usage.platform_requests') }}</th>
                    <th class="px-4 py-3">{{ __('admin.data_explorer.usage.personal_requests') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($per_user as $usage)
                    @php
                        $usesPlatform = $usage->platform_requests_count > 0;
                        $usesPersonal = $usage->personal_requests_count > 0;
                    @endphp
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $usage->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($usesPlatform && $usesPersonal)
                                <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-gc-yellow/10 text-gc-yellow border border-gc-yellow/30">
                                    {{ __('admin.data_explorer.usage.source_both') }}
                                </span>
                            @elseif ($usesPersonal)
                                <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/30">
                                    {{ __('admin.data_explorer.usage.source_personal') }}
                                </span>
                            @elseif ($usesPlatform)
                                <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-green-500/10 text-green-400 border border-green-500/30">
                                    {{ __('admin.data_explorer.usage.source_platform') }}
                                </span>
                            @else
                                <span class="text-xs text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $usage->platform_requests_count }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $usage->personal_requests_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.data_explorer.usage.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
