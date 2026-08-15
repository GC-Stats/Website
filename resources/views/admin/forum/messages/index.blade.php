{{--
    GC-Stats — Admin: recent forum messages feed

    Flat reverse-chronological list of every forum message, across every
    thread — the "moderate without opening each forum" list, distinct from
    the system-flagged queue at admin/moderation and from browsing a single
    thread's own view.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.forum_messages.title'))

@section('content')
    <p class="text-sm text-gray-500 mb-6">{{ __('admin.forum_messages.subtitle') }}</p>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.forum.messages.index') }}"
           class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ ! $onlyHidden ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
            {{ __('admin.forum_messages.filter_all') }}
        </a>
        <a href="{{ route('admin.forum.messages.index', ['hidden' => 1]) }}"
           class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all {{ $onlyHidden ? 'bg-gc-yellow text-black' : 'text-gray-400 bg-white/5 hover:text-white' }}">
            {{ __('admin.forum_messages.filter_hidden') }}
        </a>
    </div>

    @if (in_array(session('status'), ['message-hidden', 'message-unhidden', 'message-deleted'], true))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3 mb-6">
            {{ __('admin.forum_messages.'.str_replace('-', '_', session('status'))) }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($messages as $message)
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-5 shadow-xl space-y-3">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($message->thread)
                        <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-white/5 text-gray-300">
                            {{ __('forum.category.'.$message->thread->category) }}
                        </span>
                    @endif
                    @if ($message->isHidden())
                        <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-red-500/20 text-red-400">
                            {{ __('admin.forum_messages.hidden') }}
                        </span>
                    @endif
                    <span class="text-xs text-gray-500">{{ $message->created_at->diffForHumans() }}</span>
                </div>

                <div class="text-gray-300 text-sm whitespace-pre-line break-words bg-white/5 rounded-lg px-3 py-2 border border-white/10 space-y-1">
                    @foreach ($message->parseBody() as $segment)
                        @if ($segment['type'] === 'text')
                            <span>{!! $segment['html'] !!}</span>
                        @elseif ($segment['type'] === 'embed')
                            <x-forum.safe-embed-card :type="$segment['entity_type']" :model="$segment['model']" :variant="$segment['variant']" :stats="$segment['stats']" :filters="$segment['filters']" :match-data="$segment['match_data']" />
                        @elseif ($segment['type'] === 'gif')
                            <img src="{{ $segment['url'] }}" alt="GIF" loading="lazy" class="not-prose block max-w-[12rem] max-h-[12rem] rounded-lg my-1 object-contain">
                        @else
                            <span class="text-xs text-gray-600 italic">{{ __('forum.embed.missing') }}</span>
                        @endif
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-white/10">
                    <div class="text-xs text-gray-400">
                        {{ $message->user?->name ?? __('admin.forum_messages.deleted_user') }}
                        @if ($message->user?->username)
                            <span class="text-gray-500">{{ '@'.$message->user->username }}</span>
                        @endif
                        @if ($message->thread)
                            <a href="{{ route('forum.threads.show', $message->thread_id) }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-gc-yellow transition ml-2">
                                {{ __('admin.forum_messages.view_in_context') }}
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($message->isHidden())
                            <form method="POST" action="{{ route('admin.forum.messages.unhide', $message) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                    {{ __('admin.forum_messages.unhide') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.forum.messages.hide', $message) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                    {{ __('admin.forum_messages.hide') }}
                                </button>
                            </form>
                        @endif

                        @can('forum.delete')
                            <form method="POST" action="{{ route('admin.forum.messages.destroy', $message) }}"
                                  onsubmit="return confirm('{{ __('admin.forum_messages.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20">
                                    {{ __('admin.forum_messages.delete') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-8 text-center">
                <p class="text-sm text-gray-500">{{ __('admin.forum_messages.empty') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $messages->links() }}
    </div>
@endsection
