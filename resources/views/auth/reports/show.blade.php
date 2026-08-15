{{--
    GC-Stats — My report detail

    Shows a reporter the outcome of a report they filed (category, what/who
    was reported, their original reason, the moderator's note and who
    handled it) — reached from the "report resolved" notification, see
    NotificationController::open() / UserReportService::resolve().

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('account.reports.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-6">
            <div class="border-b border-border-subtle pb-6">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('account.reports.title') }}</h1>
            </div>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-gc-yellow text-black">
                        {{ __('admin.reports.category.'.$report->category) }}
                    </span>
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-white/5 text-gray-300">
                        {{ __('admin.reports.status.'.$report->status) }}
                    </span>
                    <span class="text-xs text-gray-500">{{ $report->created_at->format('Y-m-d H:i') }}</span>
                </div>

                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('account.reports.subject') }}</p>
                    @if ($report->isMessageReport())
                        <p class="text-white font-semibold">
                            {{ $report->reportedMessage?->user?->name ?? __('forum.message.deleted_user') }}
                        </p>
                        @if ($report->reportedMessage)
                            <p class="text-gray-400 text-sm whitespace-pre-line mt-1 border-l-2 border-white/10 pl-3">{{ $report->reportedMessage->body }}</p>
                        @else
                            <p class="text-gray-500 text-xs italic">{{ __('account.reports.message_deleted') }}</p>
                        @endif
                    @elseif ($report->isReactionReport())
                        <p class="text-white font-semibold flex items-center gap-1.5">
                            @if ($report->emote)
                                <img src="{{ $report->emote->image_url }}" alt="{{ $report->emote->name }}" class="w-4 h-4 object-contain">
                                {{ $report->emote->name }}
                            @else
                                {{ __('account.reports.emote_deleted') }}
                            @endif
                        </p>
                        @if ($report->reactable instanceof \App\Models\News)
                            <p class="text-gray-500 text-xs mt-1">{{ $report->reactable->title }}</p>
                        @endif
                    @else
                        <p class="text-white font-semibold">
                            {{ $report->reportedUser?->name ?? '—' }}
                            @if ($report->reportedUser?->username)
                                <span class="text-gray-500 font-normal">{{ '@'.$report->reportedUser->username }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('account.reports.your_reason') }}</p>
                    <p class="text-gray-300 whitespace-pre-line">{{ $report->reason }}</p>
                </div>

                @if ($report->reviewedBy)
                    <div class="pt-4 border-t border-white/10">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('account.reports.resolution_note') }}</p>
                        <p class="text-gray-300 whitespace-pre-line">{{ $report->resolution_note ?: __('account.reports.no_note') }}</p>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ __('account.reports.reviewed_by', [
                                'name' => $report->reviewedBy->username ? $report->reviewedBy->name.' @'.$report->reviewedBy->username : $report->reviewedBy->name,
                                'date' => $report->reviewed_at?->format('Y-m-d H:i'),
                            ]) }}
                        </p>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
