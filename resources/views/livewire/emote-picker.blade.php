<?php

/**
 * GC-Stats — Emote picker Livewire component
 *
 * Fully generic, reusable emote selector: searches the active Emote
 * catalog and, on click, dispatches an event carrying the chosen emote's
 * id — it knows nothing about reactions, news, or any other domain. Embed
 * it anywhere a "pick an emote" UI is needed; pass `event-name` to scope
 * the dispatched event to a specific listener (see
 * resources/views/livewire/reaction-bar.blade.php for the pattern).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use App\Models\Emote;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public string $eventName = 'emote-selected';

    public function mount(?string $eventName = null): void
    {
        $this->eventName = $eventName ?? 'emote-selected';
    }

    public function select(int $emoteId): void
    {
        $this->dispatch($this->eventName, emoteId: $emoteId);
    }

    public function with(): array
    {
        $term = trim($this->search);

        return [
            'emotes' => Emote::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                ->orderBy('name')
                ->limit(120)
                ->get(),
        ];
    }
}; ?>

<div class="w-64 bg-bg-main border border-white/10 rounded-xl shadow-2xl overflow-hidden">
    <div class="p-2 border-b border-white/5">
        <input type="text" wire:model.live.debounce.300ms="search" autocomplete="off"
               placeholder="{{ __('reactions.picker.search_placeholder') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
    </div>

    <div class="max-h-56 overflow-y-auto grid grid-cols-6 gap-1 p-2">
        @forelse ($emotes as $emote)
            <button type="button" wire:click="select({{ $emote->id }})" title="{{ $emote->name }}"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 transition">
                <img src="{{ $emote->image_url }}" alt="{{ $emote->name }}" class="w-6 h-6 object-contain" loading="lazy">
            </button>
        @empty
            <p class="col-span-6 text-center text-xs text-gray-500 py-4">{{ __('reactions.picker.empty') }}</p>
        @endforelse
    </div>
</div>
