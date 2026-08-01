{{--
    GC-Stats — My change requests

    Lists every change request the signed-in user has submitted (see
    PlayerChangeRequestController), most recent first. Read-only overview —
    detail + discussion lives on show.blade.php.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('account.change_requests.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <div class="border-b border-border-subtle pb-6">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('account.change_requests.title') }}</h1>
                <p class="text-sm text-gray-400 mt-2">{{ __('account.change_requests.intro') }}</p>
            </div>

            <div class="space-y-3">
                @forelse ($requests as $changeRequest)
                    <a href="{{ route('account.change-requests.show', $changeRequest) }}"
                       class="block bg-bg-card border border-border-subtle rounded-sm p-5 shadow-xl hover:border-gc-yellow/40 transition">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    {{ $changeRequest->subject?->name ?? $changeRequest->subject?->handle ?? '#'.$changeRequest->subject_id }}
                                    <span class="text-gray-500 font-normal">{{ '· '.ucfirst($changeRequest->subject_type) }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $changeRequest->items->pluck('field')->map(fn ($field) => __('admin.change_requests.fields.'.$field))->implode(', ') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <x-change-request-status-badge :status="$changeRequest->status" />
                                <span class="text-xs text-gray-500">{{ $changeRequest->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="bg-bg-card border border-border-subtle rounded-sm p-8 text-center">
                        <p class="text-sm text-gray-500">{{ __('account.change_requests.empty') }}</p>
                    </div>
                @endforelse
            </div>

            {{ $requests->links() }}
        </section>
    </div>
@endsection
