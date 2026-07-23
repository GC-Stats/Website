{{--
    GC-Stats — Admin: report detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.reports.title'))

@section('content')
    <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.reports.back_to_list') }}
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-gc-yellow text-black">
                        {{ __('admin.reports.category.'.$report->category) }}
                    </span>
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-white/5 text-gray-300">
                        {{ __('admin.reports.status.'.$report->status) }}
                    </span>
                    <span class="text-xs text-gray-500">{{ $report->created_at->format('Y-m-d H:i') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @if ($report->isReactionReport())
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.reports.reaction') }}</p>
                            <p class="text-white font-semibold flex items-center gap-1.5">
                                <img src="{{ $report->emote->image_url }}" alt="{{ $report->emote->name }}" class="w-4 h-4 object-contain">
                                {{ $report->emote->name }}
                            </p>
                            @if ($report->reactable instanceof \App\Models\News)
                                <a href="{{ route('news.show', $report->reactable->slug) }}" target="_blank" rel="noopener" class="text-gray-500 text-xs hover:text-gc-yellow transition truncate">
                                    {{ $report->reactable->title }}
                                </a>
                            @endif
                        </div>
                    @else
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.reports.reported_user') }}</p>
                            <p class="text-white font-semibold">
                                {{ $report->reportedUser?->name ?? '—' }}
                                @if ($report->reportedUser?->username)
                                    <span class="text-gray-500 font-normal">{{ '@'.$report->reportedUser->username }}</span>
                                @endif
                            </p>
                            <p class="text-gray-500 text-xs">{{ $report->reportedUser?->email }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.reports.reporter') }}</p>
                        <p class="text-white font-semibold">
                            {{ $report->reporter?->name ?? '—' }}
                            @if ($report->reporter?->username)
                                <span class="text-gray-500 font-normal">{{ '@'.$report->reporter->username }}</span>
                            @endif
                        </p>
                        <p class="text-gray-500 text-xs">{{ $report->reporter?->email }}</p>
                    </div>
                    @if ($report->team)
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.reports.team') }}</p>
                            <p class="text-white font-semibold">{{ $report->team->name }}</p>
                        </div>
                    @endif
                </div>

                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">{{ __('admin.reports.reason') }}</p>
                    <p class="text-gray-300 whitespace-pre-line">{{ $report->reason }}</p>
                </div>

                @if ($report->reviewedBy)
                    <p class="text-xs text-gray-500 pt-2 border-t border-white/10">
                        {{ __('admin.reports.reviewed_by', ['name' => $report->reviewedBy->username ? $report->reviewedBy->name.' @'.$report->reviewedBy->username : $report->reviewedBy->name, 'date' => $report->reviewed_at?->format('Y-m-d H:i')]) }}
                    </p>
                    @if ($report->resolution_note)
                        <p class="text-sm text-gray-300 italic">{{ $report->resolution_note }}</p>
                    @endif
                @endif
            </div>

            @can('reports.resolve')
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.reports.resolve.title') }}</h2>

                    <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="status" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('admin.reports.resolve.status_label') }}
                            </label>
                            <select id="status" name="status" required
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                @foreach ($statuses as $option)
                                    <option value="{{ $option }}" @selected(old('status') === $option)>{{ __('admin.reports.status.'.$option) }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="resolution_note" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                {{ __('admin.reports.resolve.note_label') }}
                            </label>
                            <textarea id="resolution_note" name="resolution_note" rows="3"
                                      class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('resolution_note') }}</textarea>
                            <p class="text-xs text-gray-500 mt-2">{{ __('admin.reports.resolve.note_help') }}</p>
                        </div>

                        <button type="submit"
                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                            {{ __('admin.reports.resolve.submit') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="space-y-6">
            @if ($report->isReactionReport())
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.reports.reactors_title') }}</h2>

                    <div class="space-y-1 max-h-96 overflow-y-auto">
                        @forelse ($report->reactingUsers() as $reaction)
                            <div class="flex items-center justify-between gap-2 py-1.5 border-b border-b-white/10 last:border-b-0">
                                <span class="text-sm text-white truncate">{{ $reaction->user->name }}</span>
                                @can('sanctions.create')
                                    <x-sanction-modal :user="$reaction->user">
                                        <button type="button" class="text-gray-500 hover:text-gc-yellow transition shrink-0" title="{{ __('admin.reports.issue_sanction') }}">
                                            @svg('fas-gavel', 'w-3 h-3', ['aria-hidden' => 'true'])
                                        </button>
                                    </x-sanction-modal>
                                @endcan
                            </div>
                        @empty
                            <p class="text-xs text-gray-500">{{ __('admin.reports.no_reactors') }}</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($report->reportedUser)
                <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.reports.reported_user_history') }}</h2>

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.reports.connected_accounts') }}</p>
                        @forelse ($report->reportedUser->socialAccounts as $account)
                            <p class="text-xs text-gray-400">{{ ucfirst($account->provider) }} — {{ $account->nickname }}</p>
                        @empty
                            <p class="text-xs text-gray-500">—</p>
                        @endforelse
                    </div>

                    <div class="pt-3 border-t border-white/10">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.reports.active_sanctions') }}</p>
                        @forelse ($report->reportedUser->sanctions->filter->isActive() as $sanction)
                            <p class="text-xs text-red-400">{{ __('admin.sanctions.type.'.$sanction->type) }} — {{ $sanction->reason }}</p>
                        @empty
                            <p class="text-xs text-gray-500">{{ __('admin.reports.no_sanctions') }}</p>
                        @endforelse
                    </div>

                    @can('sanctions.create')
                        <x-sanction-modal :user="$report->reportedUser">
                            <button type="button"
                                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
                                {{ __('admin.reports.issue_sanction') }}
                            </button>
                        </x-sanction-modal>
                    @endcan
                </div>
            @endif
        </div>
    </div>
@endsection
