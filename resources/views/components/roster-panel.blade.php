{{--
    GC-Stats — Roster panel

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'current',
    'history',
    'search',
    'searchResults',
    'searchUrl',
    'addUrl',
    'updateUrl',
    'deleteUrl',
    'roles',
    'title',
    'historyTitle',
    'addLabel',
    'roleLabel',
    'joinedAtLabel',
    'leftAtLabel',
    'saveLabel',
    'searchPlaceholder',
    'searchSubmitLabel',
    'assignLabel',
    'removeLabel',
    'removeConfirmTitle',
    'removeConfirmBody',
    'searchEmptyLabel',
    'currentEmptyLabel',
    'historyEmptyLabel',
    'headingTag' => 'h3',
])

<div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
    <{{ $headingTag }} class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</{{ $headingTag }}>

    <div class="space-y-2">
        @forelse ($current as $entry)
            <div class="flex flex-wrap items-center gap-2 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                <p class="text-sm text-white font-semibold flex-1 min-w-[8rem]">{{ $entry->player_handle }}</p>

                <form method="POST" action="{{ $updateUrl($entry) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    @method('PUT')
                    <select name="role" aria-label="{{ $roleLabel }}"
                            class="w-32 bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected($entry->role === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="joined_at" value="{{ $entry->joined_at }}" aria-label="{{ $joinedAtLabel }}"
                           class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                    <input type="date" name="left_at" value="{{ $entry->left_at }}" aria-label="{{ $leftAtLabel }}"
                           class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
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
            <p class="text-xs text-gray-500">{{ $currentEmptyLabel }}</p>
        @endforelse
    </div>

    <x-modal :title="$addLabel" :open-by-default="$search !== ''">
        <x-slot:trigger>
            <button type="button"
                    class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ $addLabel }}
            </button>
        </x-slot:trigger>

        <form method="GET" action="{{ $searchUrl }}" class="flex gap-2">
            <input type="text" name="player_q" x-ref="search" value="{{ $search }}" placeholder="{{ $searchPlaceholder }}"
                   class="flex-1 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                {{ $searchSubmitLabel }}
            </button>
        </form>

        @if ($search)
            <div class="space-y-2 pt-4">
                @forelse ($searchResults as $found)
                    <form method="POST" action="{{ $addUrl }}" class="flex items-center gap-2 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2">
                        @csrf
                        <input type="hidden" name="player_id" value="{{ $found->id }}">
                        <div class="flex-1">
                            <p class="text-xs text-white font-semibold">{{ $found->handle }}</p>
                        </div>
                        <select name="role" aria-label="{{ $roleLabel }}"
                                class="w-32 bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="joined_at" value="{{ now()->format('Y-m-d') }}" aria-label="{{ $joinedAtLabel }}"
                               class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <button type="submit"
                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90 shrink-0">
                            {{ $assignLabel }}
                        </button>
                    </form>
                @empty
                    <p class="text-xs text-gray-500">{{ $searchEmptyLabel }}</p>
                @endforelse
            </div>
        @endif
    </x-modal>

    <div class="pt-4 border-t border-border-subtle space-y-3">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ $historyTitle }}</p>
        <div class="space-y-2">
            @forelse ($history as $entry)
                <div class="flex flex-wrap items-center gap-2 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                    <p class="text-sm text-white font-semibold flex-1 min-w-[8rem]">{{ $entry->player_handle }}</p>

                    <form method="POST" action="{{ $updateUrl($entry) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @method('PUT')
                        <select name="role" aria-label="{{ $roleLabel }}"
                                class="w-32 bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected($entry->role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="joined_at" value="{{ $entry->joined_at }}" aria-label="{{ $joinedAtLabel }}"
                               class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <input type="date" name="left_at" value="{{ $entry->left_at }}" aria-label="{{ $leftAtLabel }}"
                               class="bg-black/40 border border-border-subtle rounded-sm px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
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
                <p class="text-xs text-gray-500">{{ $historyEmptyLabel }}</p>
            @endforelse
        </div>
    </div>
</div>
