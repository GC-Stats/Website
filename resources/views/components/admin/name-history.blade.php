{{--
    GC-Stats — Team name history list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'entries',
    'addUrl',
    'updateUrl',
    'deleteUrl',
    'title',
    'bodyLabel' => null,
    'nameLabel',
    'fromLabel',
    'untilLabel',
    'visibleLabel',
    'saveLabel',
    'addLabel',
    'removeLabel',
    'removeConfirmTitle',
    'removeConfirmBody',
    'emptyLabel',
])

<div class="space-y-3">
    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</h2>
    @if ($bodyLabel)
        <p class="text-xs text-gray-500">{{ $bodyLabel }}</p>
    @endif

    <div class="space-y-2">
        @forelse ($entries as $entry)
            <div class="flex flex-wrap items-center gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                <form method="POST" action="{{ $updateUrl($entry) }}" class="flex-1 flex flex-wrap items-center gap-2">
                    @csrf
                    @method('PUT')
                    <input type="text" name="name" value="{{ $entry->name }}" required aria-label="{{ $nameLabel }}"
                           class="flex-1 min-w-[8rem] bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    <input type="date" name="from" value="{{ $entry->from->format('Y-m-d') }}" aria-label="{{ $fromLabel }}"
                           class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                    <input type="date" name="until" value="{{ $entry->until?->format('Y-m-d') }}" aria-label="{{ $untilLabel }}"
                           class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                    <label class="flex items-center gap-1.5 text-[10px] text-gray-400 shrink-0">
                        <input type="checkbox" name="is_visible" value="1" @checked($entry->is_visible)
                               class="rounded-sm border-border-subtle bg-black/40 text-gc-yellow focus:ring-gc-yellow focus:ring-offset-0">
                        {{ $visibleLabel }}
                    </label>
                    <button type="submit"
                            class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-white/10 border border-border-subtle text-white hover:bg-white/20">
                        {{ $saveLabel }}
                    </button>
                </form>

                <form method="POST" action="{{ $deleteUrl($entry) }}">
                    @csrf
                    @method('DELETE')
                    <x-confirm-modal
                        :title="$removeConfirmTitle"
                        :body="$removeConfirmBody($entry)"
                        :trigger-label="$removeLabel"
                        :submit-label="$removeLabel"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                    />
                </form>
            </div>
        @empty
            <p class="text-xs text-gray-500">{{ $emptyLabel }}</p>
        @endforelse
    </div>

    <form method="POST" action="{{ $addUrl }}" class="flex flex-wrap items-center gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
        @csrf
        <input type="text" name="name" required aria-label="{{ $nameLabel }}" placeholder="{{ $nameLabel }}"
               class="flex-1 min-w-[8rem] bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
        <input type="date" name="from" required aria-label="{{ $fromLabel }}"
               class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        <input type="date" name="until" required aria-label="{{ $untilLabel }}"
               class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
        <label class="flex items-center gap-1.5 text-[10px] text-gray-400 shrink-0">
            <input type="checkbox" name="is_visible" value="1" checked
                   class="rounded-sm border-border-subtle bg-black/40 text-gc-yellow focus:ring-gc-yellow focus:ring-offset-0">
            {{ $visibleLabel }}
        </label>
        <button type="submit"
                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 shrink-0">
            {{ $addLabel }}
        </button>
    </form>
</div>
