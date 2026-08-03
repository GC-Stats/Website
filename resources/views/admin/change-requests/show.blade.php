{{--
    GC-Stats — Admin: change request detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.change_requests.title'))

@php
    $subjectLabel = $request->subject?->name ?? $request->subject?->handle ?? ('#'.$request->subject_id);
    $subjectRoute = 'admin.'.\Illuminate\Support\Str::plural($request->subject_type).'.show';
@endphp

@section('content')
    <a href="{{ route('admin.change-requests.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.change_requests.back_to_list') }}
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <x-change-request-status-badge :status="$request->status" />
                    <span class="text-xs text-gray-500">{{ $request->created_at->format('Y-m-d H:i') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.subject') }}</p>
                        @if (Route::has($subjectRoute) && $request->subject)
                            <a href="{{ route($subjectRoute, $request->subject) }}" target="_blank" rel="noopener" class="text-white font-semibold hover:text-gc-yellow transition">
                                {{ $subjectLabel }}
                            </a>
                        @else
                            <p class="text-white font-semibold">{{ $subjectLabel }}</p>
                        @endif
                        <p class="text-gray-500 text-xs">{{ ucfirst($request->subject_type) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.requested_by') }}</p>
                        @if ($request->isSystemGenerated())
                            <p class="text-gc-yellow font-semibold">{{ __('admin.change_requests.system_generated') }}</p>
                        @else
                            <p class="text-white font-semibold">
                                {{ $request->requestedBy?->name ?? '—' }}
                                @if ($request->requestedBy?->username)
                                    <span class="text-gray-500 font-normal">{{ '@'.$request->requestedBy->username }}</span>
                                @endif
                            </p>
                            @if ($request->subject_type === 'player' && $request->requestedBy && $request->subject?->user_id === $request->requestedBy->id)
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gc-yellow mt-1">
                                    {{ __('admin.change_requests.requested_by_linked_player') }}
                                </p>
                            @endif
                        @endif
                    </div>
                </div>

                @if ($request->reason)
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.reason') }}</p>
                        <p class="text-gray-300 whitespace-pre-line">{{ $request->reason }}</p>
                    </div>
                @endif

                @if ($request->closedBy || $request->closed_at)
                    <p class="text-xs text-gray-500 pt-2 border-t border-white/10">
                        {{ __('admin.change_requests.resolved_by', ['name' => $request->closedBy?->name ?? '—', 'date' => $request->closed_at?->format('Y-m-d H:i')]) }}
                    </p>
                @endif

                @can('change-requests.reject')
                    @unless ($request->isClosed())
                        <form method="POST" action="{{ route('admin.change-requests.withdraw', $request) }}" onsubmit="return confirm('{{ __('admin.change_requests.withdraw') }}?')">
                            @csrf
                            <button type="submit" class="text-xs text-red-400 hover:text-red-300 transition">
                                {{ __('admin.change_requests.withdraw') }}
                            </button>
                        </form>
                    @endunless

                    @if ($request->sanctioned_at)
                        <p class="text-xs text-gray-500 pt-3 border-t border-white/10">
                            {{ __('admin.change_requests.sanction.already_issued', ['date' => $request->sanctioned_at->format('Y-m-d H:i')]) }}
                        </p>
                    @elseif (! $request->isSystemGenerated() && $request->requestedBy)
                        <form method="POST" action="{{ route('admin.change-requests.sanction', $request) }}"
                              onsubmit="return confirm('{{ __('admin.change_requests.sanction.confirm') }}')"
                              class="flex flex-wrap items-end gap-2 pt-3 border-t border-white/10">
                            @csrf
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                                    {{ __('admin.change_requests.sanction.duration_label') }}
                                </label>
                                <x-styled-select name="duration" selected="60"
                                    :options="collect(['30', '60', '90', '180', 'permanent'])->mapWithKeys(fn ($d) => [$d => __('admin.change_requests.sanction.duration.'.$d)])" />
                            </div>
                            <div class="flex-1 min-w-[10rem]">
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                                    {{ __('admin.change_requests.sanction.reason_label') }}
                                </label>
                                <input type="text" name="reason" required
                                       class="w-full h-[42px] bg-white/5 border border-white/10 rounded-lg px-3 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                            </div>
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
                                {{ __('admin.change_requests.sanction.submit') }}
                            </button>
                            @error('reason')
                                <p class="text-xs text-red-400 w-full">{{ $message }}</p>
                            @enderror
                            @error('duration')
                                <p class="text-xs text-red-400 w-full">{{ $message }}</p>
                            @enderror
                        </form>
                    @endif
                @endcan
            </div>

            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-hidden divide-y divide-white/10">
                @foreach ($request->items as $item)
                    <div class="p-6 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-white">{{ __('admin.change_requests.fields.'.$item->field) }}</span>
                            <x-change-request-status-badge :status="$item->status" context="item" />
                        </div>

                        <x-change-request-item-value :item="$item" />

                        @if ($item->status === \App\Models\ChangeRequestItem::STATUS_ACCEPTED)
                            <p class="text-xs {{ $item->isApplied() ? 'text-green-400' : 'text-red-400' }}">
                                {{ $item->isApplied() ? __('admin.change_requests.applied') : ($item->apply_error ? __('admin.change_requests.apply_failed', ['error' => $item->apply_error]) : __('admin.change_requests.not_applied_yet')) }}
                            </p>
                        @endif

                        @if ($item->resolvedBy)
                            <p class="text-xs text-gray-500">
                                {{ __('admin.change_requests.resolved_by', ['name' => $item->resolvedBy->name, 'date' => $item->resolved_at?->format('Y-m-d H:i')]) }}
                            </p>
                            @if ($item->resolution_note)
                                <p class="text-xs text-gray-300 italic">{{ $item->resolution_note }}</p>
                            @endif
                        @endif

                        @if ($item->status === \App\Models\ChangeRequestItem::STATUS_PENDING)
                            <div class="flex flex-wrap items-start gap-2 pt-2">
                                @can('change-requests.approve')
                                    <form method="POST" action="{{ route('admin.change-requests.items.accept', $item) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="text" name="resolution_note" placeholder="{{ __('admin.change_requests.resolution_note_label') }}"
                                               class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition w-48">
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-green-500/10 border border-green-500/40 text-green-400 hover:bg-green-500/20">
                                            {{ __('admin.change_requests.accept') }}
                                        </button>
                                    </form>
                                @endcan
                                @can('change-requests.reject')
                                    <form method="POST" action="{{ route('admin.change-requests.items.reject', $item) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="text" name="resolution_note" placeholder="{{ __('admin.change_requests.resolution_note_label') }}"
                                               class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition w-48">
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
                                            {{ __('admin.change_requests.reject') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.change_requests.discussion') }}</h2>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse ($request->messages as $message)
                        <div class="text-sm {{ $message->type === 'system' ? 'text-gray-500 italic' : 'text-gray-300' }}">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-0.5">
                                {{ $message->user?->name ?? __('admin.change_requests.system_message') }}
                                <span class="font-normal normal-case text-gray-600">— {{ $message->created_at->diffForHumans() }}</span>
                            </p>
                            <p class="whitespace-pre-line">{{ $message->body }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">{{ __('admin.change_requests.no_messages') }}</p>
                    @endforelse
                </div>

                @if ($request->isResolved())
                    <p class="text-xs text-gray-500 pt-3 border-t border-white/10 italic">{{ __('admin.change_requests.discussion_closed') }}</p>
                @elseif (auth()->user()->can('change-requests.comment'))
                    <form method="POST" action="{{ route('admin.change-requests.messages.store', $request) }}" class="pt-3 border-t border-white/10 space-y-2">
                        @csrf
                        <textarea name="body" rows="3" placeholder="{{ __('admin.change_requests.message_placeholder') }}" required
                                  class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
                        <label class="flex items-center gap-2 text-xs text-gray-400">
                            <input type="checkbox" name="needs_requester_reply" value="1" checked
                                   class="rounded border-white/20 bg-white/5 text-gc-yellow focus:ring-gc-yellow focus:ring-offset-0">
                            {{ __('admin.change_requests.needs_requester_reply') }}
                        </label>
                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.change_requests.message_submit') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
