{{--
    GC-Stats — Forum "general" section, homepage sidebar

    Sits right under the news sidebar on the homepage (see public/index.blade.php)
    — same section-header style (uppercase label + fading gradient rule) as
    public/news/_sidebar.blade.php, for visual consistency without reusing
    that partial directly (its language-notice tooltip is news-specific).

    Variables expected: $generalThreads — array of ['id','title','messages_count']
    (see HomeController::index(), latest activity first). Cached as plain arrays,
    not Eloquent models — phpredis loses the class binding on unserialize for
    cached Collections here, so avoid caching raw models under this key.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="mt-8">
    <div class="flex items-center gap-2 mb-4">
        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __('forum.title.index') }}</span>
        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
    </div>

    <div class="space-y-2">
        @forelse ($generalThreads as $thread)
            <a href="{{ route('forum.threads.show', $thread['id']) }}"
               class="flex items-center justify-between gap-2 bg-white/[0.02] hover:bg-white/[0.05] rounded-lg px-3 py-2.5 transition-all">
                <span class="text-[11px] font-bold text-white/80 truncate">{{ $thread['title'] }}</span>
                <span class="text-[9px] font-bold text-gray-600 uppercase shrink-0">{{ $thread['messages_count'] }}</span>
            </a>
        @empty
            <p class="text-[9px] font-bold uppercase tracking-widest text-gray-700 py-2">{{ __('forum.thread.empty') }}</p>
        @endforelse
    </div>

    <div class="flex items-center gap-3 mt-3">
        <a href="{{ route('forum.general.create') }}"
           class="text-[9px] font-black uppercase tracking-widest text-gc-yellow hover:text-white transition">
            {{ __('forum.general.new_thread') }}
        </a>
        <a href="{{ route('forum.index') }}"
           class="text-[9px] font-black uppercase tracking-widest text-gray-500 hover:text-white transition">
            {{ __('forum.general.view_all') }}
        </a>
    </div>
</div>
