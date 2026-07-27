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
        <a href="{{ route('admin.activity.index', array_filter(['sort' => $sort, 'direction' => $direction, 'event' => $event, 'causer_name' => $causerName, 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}"
           class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ ! $logName ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
            {{ __('admin.activity.all_logs') }}
        </a>
        @foreach ($logNames as $name)
            <a href="{{ route('admin.activity.index', array_filter(['log' => $name, 'sort' => $sort, 'direction' => $direction, 'event' => $event, 'causer_name' => $causerName, 'date_from' => $dateFrom, 'date_to' => $dateTo])) }}"
               class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ $logName === $name ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                {{ ucfirst($name) }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.activity.index') }}" class="flex flex-wrap items-end gap-2 mb-6">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        @if ($logName)
            <input type="hidden" name="log" value="{{ $logName }}">
        @endif

        <label class="block">
            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.activity.filter.action') }}</span>
            <select name="event" class="h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="">{{ __('admin.activity.filter.all_actions') }}</option>
                @foreach ($events as $option)
                    <option value="{{ $option }}" @selected($event === $option)>{{ \App\Support\ActivityDisplay::eventLabel($option) }}</option>
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

    <div x-data="{ open: false, selected: null }" class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    @foreach ([['when', 'admin.activity.when'], ['causer', 'admin.activity.causer'], ['description', 'admin.activity.description'], ['subject', 'admin.activity.subject']] as [$col, $label])
                        <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    @php
                        $changes = $activity->parsed_properties['changes'] ?? [];
                        $context = $activity->parsed_properties['context'] ?? [];

                        $subjectName = \App\Support\ActivityDisplay::subjectName($activity->subject);

                        $eventLabel = \App\Support\ActivityDisplay::eventLabel($activity->description);

                        $rowData = [
                            'when' => $activity->created_at->format('Y-m-d H:i'),
                            'causer' => $activity->causer
                                ? $activity->causer->name.($activity->causer->username ? ' @'.$activity->causer->username : '')
                                : __('admin.activity.system'),
                            'subject' => $activity->subject
                                ? class_basename($activity->subject_type).' #'.$activity->subject_id.($subjectName ? ' — '.$subjectName : '')
                                : null,
                            'logName' => $activity->log_name,
                            'eventLabel' => $eventLabel,
                            'eventCode' => $activity->description,
                            'changes' => $changes,
                            'context' => $context,
                        ];
                    @endphp
                    <tr @click="open = true; selected = {{ \Illuminate\Support\Js::from($rowData) }}"
                        class="border-b border-white/10 last:border-0 align-top cursor-pointer transition hover:bg-white/5">
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
                            {{ $eventLabel }}
                            <code class="block text-[10px] text-gray-600 mt-0.5">{{ $activity->description }}</code>
                            @if (count($changes) || count($context))
                                <span class="text-[10px] text-gray-500">
                                    {{ trans_choice(':count detail|:count details', count($changes) + count($context), ['count' => count($changes) + count($context)]) }}
                                </span>
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

        <template x-teleport="body">
            <div x-show="open" x-cloak
                 class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                 @keydown.escape.window="open = false">
                <div x-show="open" @click.away="open = false" role="dialog" aria-modal="true"
                     class="w-full max-w-lg bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto text-left">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.activity.modal.title') }}</h2>
                        <button type="button" @click="open = false" aria-label="{{ __('account.edit.cancel') }}" class="text-gray-500 hover:text-white transition">
                            @svg('fas-xmark', 'w-4 h-4', ['aria-hidden' => 'true'])
                        </button>
                    </div>

                    <template x-if="selected">
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-white" x-text="selected.eventLabel"></p>
                                <code class="block text-[10px] text-gray-600 break-all" x-text="selected.eventCode"></code>
                            </div>

                            <dl class="grid grid-cols-3 gap-x-3 gap-y-2 text-xs">
                                <dt class="text-gray-500 uppercase tracking-widest text-[10px]">{{ __('admin.activity.modal.when') }}</dt>
                                <dd class="col-span-2 text-white" x-text="selected.when"></dd>

                                <dt class="text-gray-500 uppercase tracking-widest text-[10px]">{{ __('admin.activity.modal.causer') }}</dt>
                                <dd class="col-span-2 text-white" x-text="selected.causer"></dd>

                                <template x-if="selected.subject">
                                    <div class="contents">
                                        <dt class="text-gray-500 uppercase tracking-widest text-[10px]">{{ __('admin.activity.modal.subject') }}</dt>
                                        <dd class="col-span-2 text-white" x-text="selected.subject"></dd>
                                    </div>
                                </template>
                            </dl>

                            <template x-if="selected.changes.length">
                                <div>
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.activity.modal.changes') }}</h3>
                                    <div class="border border-white/10 rounded-lg overflow-hidden">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500">
                                                    <th class="px-3 py-2 text-left">{{ __('admin.activity.modal.field') }}</th>
                                                    <th class="px-3 py-2 text-left">{{ __('admin.activity.modal.old_value') }}</th>
                                                    <th class="px-3 py-2 text-left">{{ __('admin.activity.modal.new_value') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="change in selected.changes" :key="change.field">
                                                    <tr class="border-t border-white/10">
                                                        <td class="px-3 py-2 text-gray-400" x-text="change.label"></td>
                                                        <td class="px-3 py-2 text-red-400/80 break-all" x-text="change.old"></td>
                                                        <td class="px-3 py-2 text-green-400/80 break-all" x-text="change.new"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <template x-if="selected.context.length">
                                <div>
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.activity.modal.other_details') }}</h3>
                                    <dl class="grid grid-cols-3 gap-x-3 gap-y-1.5 text-xs">
                                        <template x-for="item in selected.context" :key="item.key">
                                            <div class="contents">
                                                <dt class="text-gray-500" x-text="item.label"></dt>
                                                <dd class="col-span-2 text-white break-all" x-text="item.value"></dd>
                                            </div>
                                        </template>
                                    </dl>
                                </div>
                            </template>

                            <template x-if="! selected.changes.length && ! selected.context.length">
                                <p class="text-xs text-gray-500">{{ __('admin.activity.modal.no_details') }}</p>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{ $activities->links() }}
@endsection
