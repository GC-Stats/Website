<?php

/**
 * GC-Stats — Generic entity picker Livewire component
 *
 * Reusable search-as-you-type combobox for any registered entity type (see
 * App\Services\SearchService::entityConfig() — player/team/user/emote today,
 * add a config entry there to support a new one, no changes needed here).
 * Same typo-tolerant multi-column ranking as the global search bar
 * (App\Services\SearchService::searchEntities()); an empty query browses the
 * type alphabetically instead of showing nothing, so the whole table is
 * explorable from an empty search box. Each result row has a "view" button
 * that opens the entity's public page in a new tab (player/team/user);
 * types with no public page yet (e.g. emote) fall back to an in-place
 * details modal instead — see partials/entity-view-button.blade.php.
 *
 * Usage:
 *   <livewire:entity-picker type="player" name="player_id" label="Player" />
 *   <livewire:entity-picker type="team" name="team_ids" :multiple="true" :selected="$team->players->pluck('id')" />
 *
 * Submits as a plain `name` (single) or `name[]` (multiple) hidden input, so
 * it drops into any native <form> exactly like team-fan-picker.blade.php.
 *
 * `thumbSize` (default 'w-8 h-5') sets the selected-chip thumbnail's size —
 * bump it (e.g. 'w-16 h-10') where the chip is the primary visual, like
 * roster-entry-card.blade.php's grid cards.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use Livewire\Volt\Component;
use App\Services\SearchService;

new class extends Component {
    public string $type;

    public string $name;

    public ?string $label = null;

    public bool $multiple = false;

    public ?string $placeholder = null;

    public int $limit = 8;

    public string $thumbSize = 'w-8 h-5';

    public string $search = '';

    /** @var list<array<string, mixed>> */
    public array $selectedItems = [];

    public ?array $infoItem = null;

    public function mount(string $type, ?string $name = null, ?string $label = null, bool $multiple = false, mixed $selected = null, ?string $placeholder = null, int $limit = 8, string $thumbSize = 'w-8 h-5'): void
    {
        $this->type = $type;
        $this->name = $name ?? $type;
        $this->label = $label;
        $this->multiple = $multiple;
        $this->placeholder = $placeholder;
        $this->limit = $limit;
        $this->thumbSize = $thumbSize;

        $ids = collect(is_iterable($selected) ? $selected : ($selected !== null ? [$selected] : []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->values();

        $service = app(SearchService::class);

        $this->selectedItems = $ids
            ->map(fn ($id) => $service->entityDetails($this->type, $id))
            ->filter()
            ->values()
            ->toArray();
    }

    public function select(int $id): void
    {
        if (collect($this->selectedItems)->contains('id', $id)) {
            $this->search = '';

            return;
        }

        $item = app(SearchService::class)->entityDetails($this->type, $id);
        if ($item === null) {
            return;
        }

        $this->selectedItems = $this->multiple
            ? [...$this->selectedItems, $item]
            : [$item];

        $this->search = '';
    }

    public function remove(int $id): void
    {
        $this->selectedItems = collect($this->selectedItems)
            ->reject(fn ($item) => $item['id'] === $id)
            ->values()
            ->toArray();
    }

    public function showInfo(int $id): void
    {
        $this->infoItem = collect($this->selectedItems)->firstWhere('id', $id)
            ?? app(SearchService::class)->entityDetails($this->type, $id);
    }

    public function closeInfo(): void
    {
        $this->infoItem = null;
    }

    public function with(): array
    {
        $excludedIds = collect($this->selectedItems)->pluck('id')->all();

        $results = collect(app(SearchService::class)->searchEntities($this->type, $this->search, $this->limit + count($excludedIds)))
            ->reject(fn ($item) => in_array($item['id'], $excludedIds, true))
            ->take($this->limit)
            ->values()
            ->toArray();

        return ['results' => $results];
    }
}; ?>

@php
    $typeLabel = __('entity-picker.type.'.$type);
@endphp

<div class="space-y-3"
     x-data="{
         open: false,
         activeIndex: -1,
         flatItems() {
             return [...this.$el.querySelectorAll('[data-entity-item]')].filter(el => el.offsetParent !== null);
         },
         setActive(index) {
             this.flatItems().forEach((el, i) => el.classList.toggle('bg-white/[0.03]', i === index));
         },
         moveDown() {
             const items = this.flatItems();
             if (!items.length) return;
             this.activeIndex = this.activeIndex < items.length - 1 ? this.activeIndex + 1 : 0;
             this.setActive(this.activeIndex);
             items[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
         },
         moveUp() {
             const items = this.flatItems();
             if (!items.length) return;
             this.activeIndex = this.activeIndex > 0 ? this.activeIndex - 1 : items.length - 1;
             this.setActive(this.activeIndex);
             items[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
         },
         confirmSelection() {
             const item = this.flatItems()[this.activeIndex];
             if (item) $wire.select(parseInt(item.dataset.entityId, 10));
         },
     }"
     @click.away="open = false; activeIndex = -1">

    @if ($label)
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ $label }}</label>
    @endif

    {{-- Selected entities: same "chip" as team-fan-picker, stacked when multiple. Thumb/flag/actions
         on their own row so the title below gets the chip's full width instead of being squeezed
         between them — narrow containers (e.g. roster-entry-card's grid cells) truncated it hard otherwise. --}}
    @if (count($selectedItems) > 0)
        <div class="space-y-2">
            @foreach ($selectedItems as $item)
                <input type="hidden" name="{{ $multiple ? "{$name}[]" : $name }}" value="{{ $item['id'] }}">

                <div class="flex flex-col gap-2 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="flex-shrink-0 {{ $thumbSize }} flex items-center justify-center">
                            @include('livewire.partials.entity-thumb', ['item' => $item, 'size' => $thumbSize])
                        </div>

                        {{-- entity-thumb already falls back to the flag when there's no photo/logo — only add it here when there IS one, to avoid showing it twice --}}
                        @if ($item['image'] && $item['country_code'])
                            <span class="fi fi-{{ strtolower($item['country_code']) }} fis rounded shadow-sm w-5 h-3.5 shrink-0" aria-label="{{ $item['country_code'] }}"></span>
                        @endif

                        <div class="flex items-center gap-1 ml-auto shrink-0">
                            @include('livewire.partials.entity-view-button', ['item' => $item, 'class' => 'w-7 h-7'])
                            <button type="button" wire:click="remove({{ $item['id'] }})"
                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                                {{ __('entity-picker.remove') }}
                            </button>
                        </div>
                    </div>

                    <span class="text-sm font-bold uppercase tracking-wider text-white truncate">{{ $item['title'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Search + browse dropdown — hidden once a single-select slot is filled --}}
    @if ($multiple || count($selectedItems) === 0)
        <div class="relative">
            <div class="group relative flex items-center">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none" aria-hidden="true">
                    <x-fas-search class="w-3.5 h-3.5 text-gray-500 group-focus-within:text-gc-yellow transition-all" aria-hidden="true" />
                </div>

                <input type="search"
                       wire:model.live.debounce.300ms="search"
                       x-ref="input"
                       x-on:focus="open = true; activeIndex = -1"
                       x-on:keydown.arrow-down.prevent="moveDown()"
                       x-on:keydown.arrow-up.prevent="moveUp()"
                       x-on:keydown.enter.prevent="confirmSelection()"
                       x-on:keydown.escape="open = false; activeIndex = -1; $refs.input.blur()"
                       autocomplete="off"
                       role="combobox"
                       aria-expanded="{{ $multiple || count($selectedItems) === 0 ? 'true' : 'false' }}"
                       aria-autocomplete="list"
                       placeholder="{{ $placeholder ?? __('entity-picker.placeholder', ['type' => $typeLabel]) }}"
                       class="block w-full py-2.5 pl-10 pr-4 text-xs font-bold tracking-[0.1em] rounded-sm bg-[#050505] border border-border-subtle text-white placeholder-gray-500 focus:outline-none focus:border-gc-yellow transition-all">

                <div wire:loading.delay wire:target="search" class="absolute right-3" role="status">
                    <div class="w-3 h-3 border-2 border-gc-yellow border-t-transparent rounded-full animate-spin" aria-hidden="true"></div>
                </div>
            </div>

            {{-- Results: opens on focus, browse mode (alphabetical) when search is empty --}}
            <div x-show="open"
                 class="absolute left-0 right-0 mt-2 overflow-hidden shadow-xl rounded-sm bg-bg-card border border-border-subtle z-20"
                 x-cloak
                 wire:key="entity-picker-panel-{{ $type }}">

                <div class="max-h-64 overflow-y-auto">
                    @forelse ($results as $item)
                        <div data-entity-item data-entity-id="{{ $item['id'] }}"
                             class="flex items-center border-l-2 border-transparent hover:bg-white/[0.03] hover:border-gc-yellow transition-all group">
                            <button type="button" wire:click="select({{ $item['id'] }})"
                                    class="flex-1 min-w-0 flex items-center gap-3 px-4 py-3 text-left">
                                <div class="flex-shrink-0 w-8 h-5 flex items-center justify-center">
                                    @include('livewire.partials.entity-thumb', ['item' => $item, 'size' => 'w-8 h-5'])
                                </div>
                                <span class="flex-1 min-w-0 truncate text-[11px] font-bold uppercase tracking-wider text-gray-400 group-hover:text-white transition-colors">
                                    {{ $item['title'] }}
                                </span>
                            </button>

                            @include('livewire.partials.entity-view-button', ['item' => $item, 'class' => 'shrink-0 w-8 h-8 mr-1'])
                        </div>
                    @empty
                        <p class="px-4 py-3 text-xs text-gray-500">
                            {{ trim($search) === ''
                                ? __('entity-picker.browse_empty', ['type' => $typeLabel])
                                : __('entity-picker.no_results', ['type' => $typeLabel, 'query' => $search]) }}
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Full-info modal — same chrome as <x-modal>, driven by $infoItem so any row (selected or in-results) can open it --}}
    <template x-teleport="body">
        <div x-show="$wire.infoItem !== null" x-cloak
             class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @keydown.escape.window="$wire.closeInfo()">
            <div @click.away="$wire.closeInfo()" role="dialog" aria-modal="true"
                 class="w-full max-w-md bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4 max-h-[85vh] overflow-y-auto">
                @if ($infoItem)
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center">
                                @include('livewire.partials.entity-thumb', ['item' => $infoItem, 'size' => 'w-10 h-10'])
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-sm font-black uppercase tracking-widest text-white truncate">{{ $infoItem['title'] }}</h2>
                                @if ($infoItem['subtitle'])
                                    <p class="text-xs text-gray-500 truncate">{{ $infoItem['subtitle'] }}</p>
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="closeInfo" title="{{ __('entity-picker.close') }}"
                                class="shrink-0 text-gray-500 hover:text-white transition">
                            <x-fas-xmark class="w-4 h-4" aria-hidden="true" />
                        </button>
                    </div>

                    @if (count($infoItem['fields'] ?? []) > 0)
                        <dl class="divide-y divide-white/5 border-t border-border-subtle">
                            @foreach ($infoItem['fields'] as $field)
                                <div class="grid grid-cols-3 gap-3 py-3">
                                    <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ $field['label'] }}</dt>
                                    <dd class="col-span-2 text-sm text-gray-200 break-words">{{ $field['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                @endif
            </div>
        </div>
    </template>
</div>
