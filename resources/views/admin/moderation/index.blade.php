{{--
    GC-Stats — Admin: moderation suspects queue

    System-flagged forum content (OpenAI moderation), distinct from the
    user-submitted queue at admin/reports. Every flagged message starts
    hidden; each row can be approved (unhidden — false positive) or marked
    actioned (stays hidden — confirmed), with a shortcut to sanction the
    poster.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.moderation.title'))

@section('content')
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ($statuses as $option)
            <a href="{{ route('admin.moderation.index', ['status' => $option]) }}"
               class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ $status === $option ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
                {{ __('admin.moderation.status.'.$option) }}
            </a>
        @endforeach
    </div>

    @if (session('status') === 'suspect-resolved')
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3 mb-6">
            {{ __('admin.moderation.resolved_status') }}
        </div>
    @elseif (session('status') === 'mute-lifted')
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3 mb-6">
            {{ __('admin.moderation.mute_lifted_status') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($suspects as $suspect)
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5 shadow-xl space-y-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-white/5 text-gray-300">
                        {{ __('admin.moderation.status.'.$suspect->status) }}
                    </span>
                    @if ($suspect->subject?->isHidden())
                        <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-red-500/20 text-red-400">
                            {{ __('admin.moderation.hidden') }}
                        </span>
                    @endif
                    @php $mute = $suspect->user?->activeGlobalMuteSanction(); @endphp
                    @if ($mute)
                        <span class="flex items-center gap-2 px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-orange-500/20 text-orange-400">
                            {{ __('admin.moderation.muted_until', ['date' => $mute->ends_at?->format('Y-m-d H:i')]) }}
                            @can('moderation.resolve')
                                @if ($mute->issued_by === null)
                                    <form method="POST" action="{{ route('admin.moderation.lift-mute', $suspect) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="underline hover:text-white transition">
                                            {{ __('admin.moderation.lift_mute') }}
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </span>
                    @endif
                    <span class="text-xs text-gray-500">{{ $suspect->created_at->diffForHumans() }}</span>
                </div>

                <div class="text-sm">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ __('admin.moderation.matched_term') }}</span>
                    <span class="text-red-400 font-semibold">{{ $suspect->matched_term }}</span>
                </div>

                <p class="text-gray-300 text-sm whitespace-pre-line bg-white/5 rounded-lg px-3 py-2 border border-white/10">{{ $suspect->body_snapshot }}</p>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-white/10">
                    <div class="text-xs text-gray-400">
                        {{ $suspect->user?->name ?? __('admin.moderation.deleted_user') }}
                        @if ($suspect->user?->username)
                            <span class="text-gray-500">{{ '@'.$suspect->user->username }}</span>
                        @endif
                        @if ($suspect->thread)
                            <a href="{{ route('forum.threads.show', $suspect->thread_id) }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-gc-yellow transition ml-2">
                                {{ __('admin.moderation.view_in_context') }}
                            </a>
                        @endif
                    </div>

                    @can('moderation.resolve')
                        @if ($suspect->status === 'pending')
                            <div class="flex items-center gap-2">
                                @can('sanctions.create')
                                    @if ($suspect->user)
                                        <x-admin.sanction-modal :user="$suspect->user">
                                            <button type="button"
                                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
                                                {{ __('admin.moderation.sanction') }}
                                            </button>
                                        </x-admin.sanction-modal>
                                    @endif
                                @endcan

                                <form method="POST" action="{{ route('admin.moderation.resolve', $suspect) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="dismissed">
                                    <button type="submit"
                                            class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                        {{ __('admin.moderation.approve') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.moderation.resolve', $suspect) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="actioned">
                                    <button type="submit"
                                            class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:brightness-110">
                                        {{ __('admin.moderation.mark_actioned') }}
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-xs text-gray-500">
                                {{ __('admin.moderation.reviewed_by', ['name' => $suspect->reviewedBy?->name ?? '—', 'date' => $suspect->reviewed_at?->format('Y-m-d H:i')]) }}
                            </p>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-8 text-center">
                <p class="text-sm text-gray-500">{{ __('admin.moderation.empty') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $suspects->links() }}
    </div>
@endsection
