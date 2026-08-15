<?php

/**
 * GC-Stats — GIF picker Livewire component
 *
 * Search-and-insert popover for the forum composer's GIF button, backed by
 * App\Services\GiphyService — same shape as emote-picker.blade.php (search
 * box + click-to-select grid), but the click dispatches the GIF's full CDN
 * URL instead of a local database id, since there's no local GIF catalog to
 * look one up in. Empty search shows Giphy's trending feed rather than
 * nothing, mirroring emote-picker's "popular" default.
 *
 * Dispatches {url} so the composer (resources/views/livewire/
 * forum-thread.blade.php) can insert a `{{gif:url}}` token — see
 * App\Models\ForumMessage::parseBody() for how that gets parsed back out.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use App\Services\GiphyService;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public string $eventName = 'gif-selected';

    public function mount(?string $eventName = null): void
    {
        $this->eventName = $eventName ?? 'gif-selected';
    }

    public function select(string $url): void
    {
        $this->dispatch($this->eventName, url: $url);
    }

    public function with(): array
    {
        $term = trim($this->search);
        $giphy = app(GiphyService::class);

        return ['gifs' => $term === '' ? $giphy->trending() : $giphy->search($term)];
    }
}; ?>

<div class="w-72 bg-bg-main border border-white/10 rounded-xl shadow-2xl overflow-hidden">
    <div class="p-2 border-b border-white/5">
        <input type="text" wire:model.live.debounce.300ms="search" autocomplete="off"
               placeholder="{{ __('forum.gif.search_placeholder') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
    </div>

    <div class="max-h-64 overflow-y-auto grid grid-cols-2 gap-1 p-2" wire:loading.class="opacity-50">
        @forelse ($gifs as $gif)
            <button type="button" wire:click="select('{{ addslashes($gif['full_url']) }}')"
                    class="rounded-lg overflow-hidden hover:ring-2 hover:ring-gc-yellow transition">
                <img src="{{ $gif['preview_url'] }}" alt="" class="w-full h-20 object-cover" loading="lazy">
            </button>
        @empty
            <p class="col-span-2 text-center text-xs text-gray-500 py-4">{{ __('forum.gif.no_results') }}</p>
        @endforelse
    </div>
</div>
