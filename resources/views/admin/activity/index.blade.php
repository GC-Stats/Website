{{--
    GC-Stats — Admin: activity log

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.activity.title'))

@section('content')
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.activity.index') }}"
           class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ ! $logName ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
            {{ __('admin.activity.all_logs') }}
        </a>
        @foreach ($logNames as $name)
            <a href="{{ route('admin.activity.index', array_filter(['log' => $name, 'event' => $event, 'causer_name' => $causerName, 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}"
               class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ $logName === $name ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                {{ ucfirst($name) }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.activity.index') }}" class="flex flex-wrap items-end gap-2 mb-6">
        @if ($logName)
            <input type="hidden" name="log" value="{{ $logName }}">
        @endif

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.activity.filter.action') }}</span>
            <select name="event" class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.activity.filter.all_actions') }}</option>
                @foreach ($events as $option)
                    <option value="{{ $option }}" @selected($event === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.activity.filter.user') }}</span>
            <input type="text" name="causer_name" value="{{ $causerName }}" placeholder="{{ __('admin.activity.filter.user_placeholder') }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.activity.filter.from') }}</span>
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.activity.filter.to') }}</span>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                   class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        </label>

        <button type="submit"
                class="h-[42px] font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            {{ __('admin.activity.filter.submit') }}
        </button>

        @if ($event || $causerName || $dateFrom || $dateTo)
            <a href="{{ route('admin.activity.index', array_filter(['log' => $logName])) }}"
               class="h-[42px] inline-flex items-center font-bold uppercase text-[10px] tracking-widest px-4 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.activity.filter.reset') }}
            </a>
        @endif
    </form>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto"
         x-data="GCS.sortableTable()">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['when', 'admin.activity.when'], ['causer', 'admin.activity.causer'], ['description', 'admin.activity.description'], ['subject', 'admin.activity.subject']] as [$col, $label])
                        <th class="px-4 py-3" @click="sortBy('{{ $col }}')">
                            <span class="group inline-flex items-center gap-1 hover:text-white transition cursor-pointer select-none">
                                {{ __($label) }}
                                @include('admin.partials.sort-arrows', ['col' => $col])
                            </span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody x-ref="tbody">
                @forelse ($activities as $activity)
                    <tr data-row data-when="{{ $activity->created_at->timestamp }}" data-causer="{{ $activity->causer->name ?? __('admin.activity.system') }}" data-description="{{ $activity->description }}" data-subject="{{ $activity->subject ? class_basename($activity->subject_type).' #'.$activity->subject_id : '' }}"
                        class="border-b border-white/10 last:border-0 align-top">
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-white">
                            @if ($activity->causer)
                                {{ $activity->causer->name }}
                                @if ($activity->causer->username)
                                    <span class="text-gray-500">{{ '@'.$activity->causer->username }}</span>
                                @endif
                            @else
                                {{ __('admin.activity.system') }}
                            @endif
                        </td>
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
