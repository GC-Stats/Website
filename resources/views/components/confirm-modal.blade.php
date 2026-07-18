{{--
    GC-Stats — Confirm modal

    In-page confirmation dialog instead of the browser's confirm(). Place
    inside a <form> to submit it on confirm, or pass `onConfirm` with an
    Alpine expression (e.g. "deletePasskey(passkey.id)") to run JS instead.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'title',
    'body',
    'triggerLabel',
    'submitLabel',
    'onConfirm' => null,
    'triggerClass' => 'font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10',
    'submitClass' => 'bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20',
])

<div x-data="{ open: false }" style="display: contents">
    <button type="button" @click="open = true" {{ $attributes->merge(['class' => $triggerClass]) }}>
        {{ $triggerLabel }}
    </button>

    <div x-show="open" x-cloak
         class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         @keydown.escape.window="open = false">
        <div @click.away="open = false" role="dialog" aria-modal="true"
             class="w-full max-w-sm bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</h2>
            <p class="text-xs text-gray-500">{{ $body }}</p>

            {{ $slot }}

            <div class="flex gap-3">
                <button type="button" @click="open = false"
                        class="flex-1 font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                    {{ __('account.edit.cancel') }}
                </button>
                @if ($onConfirm)
                    <button type="button" @click="{{ $onConfirm }}; open = false"
                            class="flex-1 font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 {{ $submitClass }}">
                        {{ $submitLabel }}
                    </button>
                @else
                    <button type="submit"
                            class="flex-1 font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 {{ $submitClass }}">
                        {{ $submitLabel }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
