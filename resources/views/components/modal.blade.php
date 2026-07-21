{{--
    GC-Stats — Generic form modal

    Trigger (via the 'trigger' slot) that opens a dialog holding arbitrary
    content — for anything heavier than <x-confirm-modal>'s yes/no.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
{{--
    $openByDefault: pass true to start open — needed for modals whose
    content is a GET search form (full page reload on submit resets Alpine
    state), so the modal doesn't appear to "close" after searching.
--}}
@props(['title', 'maxWidth' => 'max-w-md', 'openByDefault' => false])

<div x-data="{ open: {{ $openByDefault ? 'true' : 'false' }} }" style="display: contents">
    <span @click="open = true; $nextTick(() => $refs.search && ($refs.search.value = ''))" style="display: contents">
        {{ $trigger }}
    </span>

    <template x-teleport="body">
        <div x-show="open" x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @keydown.escape.window="open = false">
            <div @click.away="open = false" role="dialog" aria-modal="true"
                 class="w-full {{ $maxWidth }} bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto text-left">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</h2>
                    <button type="button" @click="open = false" aria-label="{{ __('account.edit.cancel') }}" class="text-gray-500 hover:text-white transition">
                        @svg('fas-xmark', 'w-4 h-4', ['aria-hidden' => 'true'])
                    </button>
                </div>

                {{ $slot }}
            </div>
        </div>
    </template>
</div>
