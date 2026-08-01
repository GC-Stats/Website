{{--
    GC-Stats — My change request detail

    Read-only view of one of the signed-in user's own change requests
    (status, per-item accept/reject outcome) plus the moderation discussion,
    which the user can post into. UserChangeRequestController 403s before
    this renders if $request->requested_by isn't the current user.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('account.change_requests.title'))

@php
    $subjectLabel = $request->subject?->name ?? $request->subject?->handle ?? ('#'.$request->subject_id);
    $subjectRoute = match ($request->subject_type) {
        'player' => 'players.show',
        'team' => 'teams.show',
        default => null,
    };
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <a href="{{ route('account.change-requests.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition">
                &larr; {{ __('account.change_requests.back_to_list') }}
            </a>

            @if (session('status') === 'change-request-message-added')
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __('admin.change_requests.message_submit') }}
                </div>
            @endif

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <x-change-request-status-badge :status="$request->status" />
                    <span class="text-xs text-gray-500">{{ $request->created_at->format('Y-m-d H:i') }}</span>
                </div>

                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('account.change_requests.subject') }}</p>
                    @if ($subjectRoute && Route::has($subjectRoute) && $request->subject)
                        <a href="{{ route($subjectRoute, $request->subject) }}" class="text-white font-semibold hover:text-gc-yellow transition">
                            {{ $subjectLabel }}
                        </a>
                    @else
                        <p class="text-white font-semibold">{{ $subjectLabel }}</p>
                    @endif
                    <p class="text-gray-500 text-xs">{{ ucfirst($request->subject_type) }}</p>
                </div>

                @if ($request->reason)
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.change_requests.reason') }}</p>
                        <p class="text-gray-300 whitespace-pre-line">{{ $request->reason }}</p>
                    </div>
                @endif

                @if ($request->closedBy || $request->closed_at)
                    <p class="text-xs text-gray-500 pt-2 border-t border-border-subtle">
                        {{ __('admin.change_requests.resolved_by', ['name' => $request->closedBy?->name ?? '—', 'date' => $request->closed_at?->format('Y-m-d H:i')]) }}
                    </p>
                @endif
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm shadow-xl overflow-hidden divide-y divide-border-subtle">
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
                    </div>
                @endforeach
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
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
                    <p class="text-xs text-gray-500 pt-3 border-t border-border-subtle italic">{{ __('admin.change_requests.discussion_closed') }}</p>
                @else
                    <form method="POST" action="{{ route('account.change-requests.messages.store', $request) }}" class="pt-3 border-t border-border-subtle space-y-2">
                        @csrf
                        <textarea name="body" rows="3" placeholder="{{ __('admin.change_requests.message_placeholder') }}" required
                                  class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
                        @error('body')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                            {{ __('admin.change_requests.message_submit') }}
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
