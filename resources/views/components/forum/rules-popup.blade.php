{{--
    GC-Stats — Forum rules acceptance popup

    Purely presentational — visibility and the accept action both live in the
    parent's Alpine scope (see resources/views/livewire/forum-thread.blade.php
    and resources/views/public/forum/general/create.blade.php), so it shows
    up at the moment a user without prior acceptance tries to post, not on
    page load. `open` is an Alpine expression string evaluated in that
    parent scope (e.g. "rulesPopupOpen"), `onAccept` likewise (e.g.
    "acceptRules()"), and the optional `error` is a third such expression
    (e.g. "rulesAcceptFailed") shown when the parent's accept call itself
    failed (network error, expired session) rather than proceeding as if it
    had succeeded. Posting is still gated server-side regardless (see
    App\Http\Controllers\Public\ForumController::generalStore() and the
    forum-thread component's postMessage()), so this popup is a UX gate, not
    the actual enforcement.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['open', 'onAccept', 'error' => 'false'])

<div x-show="{{ $open }}" x-cloak
     class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div role="dialog" aria-modal="true"
         class="w-full max-w-lg bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto text-left">
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('forum.rules.popup_title') }}</h2>

        <p class="text-sm text-gray-300">{{ __('forum.rules.popup_intro') }}</p>

        <div class="space-y-4">
            @foreach (config('forum.rules_sections') as $index => $section)
                <div>
                    <h3 class="text-xs font-bold text-white uppercase tracking-widest mb-1 flex items-center gap-2">
                        <span class="text-gc-yellow">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}.</span>
                        {{ __('forum.rules.sections.'.$section.'.title') }}
                    </h3>
                    <p class="text-sm text-gray-300 leading-relaxed">
                        {{ __('forum.rules.sections.'.$section.'.text') }}
                    </p>
                </div>
            @endforeach
        </div>

        <p x-show="{{ $error }}" x-cloak class="text-xs text-red-400">{{ __('forum.rules.accept_failed') }}</p>

        <button type="button" @click="{{ $onAccept }}"
                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow/10 border border-gc-yellow/40 text-gc-yellow hover:bg-gc-yellow/20">
            {{ __('forum.rules.accept') }}
        </button>
    </div>
</div>
