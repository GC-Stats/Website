{{--
    GC-Stats — V2 migration info page

    Linked from the maintenance / write freeze banners: explains the planned
    V2 maintenance and which contributions are paused meanwhile
    (see App\Support\WriteFreeze).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('v2_migration.title'))
@section('description', __('v2_migration.description'))

@php
    $maintenanceAt = config('app.v2_maintenance_at') ? \Illuminate\Support\Carbon::parse(config('app.v2_maintenance_at'))->utc() : null;
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">
            <div class="pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('v2_migration.title') }}
                </h1>
            </div>

            <div class="space-y-6">
                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <p class="text-sm text-gray-300">{{ __('v2_migration.intro') }}</p>
                </div>

                @if ($maintenanceAt)
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                        <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                            <span class="text-gc-yellow">01.</span> {{ __('v2_migration.schedule.title') }}
                        </h2>
                        <div class="bg-bg-main border-l-4 border-gc-yellow p-6 space-y-1" data-utc-datetime="{{ $maintenanceAt->toIso8601String() }}">
                            <p class="text-[10px] uppercase font-bold text-gray-500">{{ __('v2_migration.schedule.starts') }}</p>
                            <p class="text-lg font-black text-white">
                                <span class="js-match-date">{{ $maintenanceAt->format('d/m/Y') }}</span>
                                <span class="text-gc-yellow js-match-time">{{ $maintenanceAt->format('H:i') }} UTC</span>
                            </p>
                            <p class="text-xs text-gray-400">{{ __('v2_migration.schedule.note') }}</p>
                        </div>
                    </div>
                @endif

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">{{ $maintenanceAt ? '02.' : '01.' }}</span> {{ __('v2_migration.limited.title') }}
                    </h2>
                    <p class="text-sm text-gray-300 mb-3">{{ __('v2_migration.limited.intro') }}</p>
                    <ul class="space-y-2 text-sm text-gray-300">
                        @foreach (__('v2_migration.limited.items') as $item)
                            <li class="flex items-start gap-2">
                                <span class="text-gc-yellow font-black">✕</span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest mb-4 border-b border-border-subtle pb-2 flex items-center gap-2">
                        <span class="text-gc-yellow">{{ $maintenanceAt ? '03.' : '02.' }}</span> {{ __('v2_migration.available.title') }}
                    </h2>
                    <p class="text-sm text-gray-300">{{ __('v2_migration.available.body') }}</p>
                </div>

                <p class="text-center text-xs font-bold uppercase tracking-widest text-gray-500">{{ __('v2_migration.thanks') }}</p>
            </div>
        </section>
    </div>
@endsection
