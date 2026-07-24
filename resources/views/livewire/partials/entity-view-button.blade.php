{{--
    GC-Stats — Entity view button

    Shared "see more" action for entity-picker.blade.php result rows and
    selected chips: opens the entity's public page in a new tab when one
    exists (App\Services\SearchService::entityConfig()'s `route` closure —
    player/team/user today), falling back to the in-place details modal
    (App\Services\SearchService::entityDetails()) for types with no public
    page yet (e.g. emote).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php $class ??= ''; @endphp

@if ($item['url'])
    <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer"
       title="{{ __('entity-picker.view') }}"
       class="flex items-center justify-center rounded-sm text-gray-500 hover:text-gc-yellow hover:bg-white/5 transition {{ $class }}">
        <x-fas-arrow-up-right-from-square class="w-3.5 h-3.5" aria-hidden="true" />
    </a>
@else
    <button type="button" wire:click.stop="showInfo({{ $item['id'] }})"
            title="{{ __('entity-picker.info') }}"
            class="flex items-center justify-center rounded-sm text-gray-500 hover:text-gc-yellow hover:bg-white/5 transition {{ $class }}">
        <x-fas-circle-info class="w-3.5 h-3.5" aria-hidden="true" />
    </button>
@endif
