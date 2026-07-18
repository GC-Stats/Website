{{--
    GC-Stats — Admin: activity log

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.activity.title'))

@section('content')
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.activity.index') }}"
           class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all {{ ! $logName ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
            {{ __('admin.activity.all_logs') }}
        </a>
        @foreach ($logNames as $name)
            <a href="{{ route('admin.activity.index', ['log' => $name]) }}"
               class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-sm transition-all {{ $logName === $name ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                {{ ucfirst($name) }}
            </a>
        @endforeach
    </div>

    <div class="bg-bg-card border border-border-subtle rounded-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-border-subtle text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.activity.when') }}</th>
                    <th class="px-4 py-3">{{ __('admin.activity.causer') }}</th>
                    <th class="px-4 py-3">{{ __('admin.activity.description') }}</th>
                    <th class="px-4 py-3">{{ __('admin.activity.subject') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr class="border-b border-border-subtle last:border-0 align-top">
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-white">{{ $activity->causer?->name ?? __('admin.activity.system') }}</td>
                        <td class="px-4 py-3 text-gray-300">
                            <code class="text-xs text-gc-yellow">{{ $activity->description }}</code>
                            @if ($activity->properties->isNotEmpty())
                                <div class="text-xs text-gray-500 mt-1">
                                    @foreach ($activity->properties as $key => $value)
                                        <span class="mr-2">{{ $key }}: {{ is_scalar($value) ? $value : json_encode($value) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            @if ($activity->subject)
                                {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.activity.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $activities->links() }}
@endsection
