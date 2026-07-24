{{--
    GC-Stats — Roster panel

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props([
    'current',
    'history',
    'addUrl',
    'syncUrl',
    'roles',
    'title',
    'historyTitle',
    'addLabel',
    'roleLabel',
    'joinedAtLabel',
    'leftAtLabel',
    'saveLabel',
    'assignLabel',
    'removeLabel',
    'removeConfirmBody',
    'currentEmptyLabel',
    'historyEmptyLabel',
    'headingTag' => 'h3',
    'pickerType' => 'player',
    'pivotField' => 'player_id',
])

@php $entryIndex = 0; @endphp

<div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
    <div class="flex items-center justify-between gap-4">
        <{{ $headingTag }} class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ $title }}</{{ $headingTag }}>

        <x-modal :title="$addLabel" max-width="max-w-lg">
            <x-slot:trigger>
                <button type="button"
                        class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                    {{ $addLabel }}
                </button>
            </x-slot:trigger>

            <form method="POST" action="{{ $addUrl }}" class="space-y-4">
                @csrf

                <livewire:entity-picker :type="$pickerType" :name="$pivotField" />

                <div class="flex flex-wrap items-center gap-2">
                    <select name="role" aria-label="{{ $roleLabel }}"
                            class="flex-1 min-w-[8rem] bg-black/40 border border-border-subtle rounded-sm px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="joined_at" value="{{ now()->format('Y-m-d') }}" aria-label="{{ $joinedAtLabel }}"
                           class="bg-black/40 border border-border-subtle rounded-sm px-3 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                </div>

                <button type="submit"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                    {{ $assignLabel }}
                </button>
            </form>
        </x-modal>
    </div>

    <form method="POST" action="{{ $syncUrl }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
            @forelse ($current as $entry)
                @include('components.partials.roster-entry-card', ['entry' => $entry, 'index' => $entryIndex])
                @php $entryIndex++; @endphp
            @empty
                <p class="text-xs text-gray-500 col-span-full">{{ $currentEmptyLabel }}</p>
            @endforelse
        </div>

        <div class="pt-4 border-t border-border-subtle space-y-3">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ $historyTitle }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                @forelse ($history as $entry)
                    @include('components.partials.roster-entry-card', ['entry' => $entry, 'index' => $entryIndex])
                    @php $entryIndex++; @endphp
                @empty
                    <p class="text-xs text-gray-500 col-span-full">{{ $historyEmptyLabel }}</p>
                @endforelse
            </div>
        </div>

        <button type="submit"
                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
            {{ $saveLabel }}
        </button>
    </form>
</div>
