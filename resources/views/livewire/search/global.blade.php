<?php

/**
 * GC-Stats — Global search Livewire component
 *
 * Volt component powering the global search bar: searches players, teams
 * and tournaments by name/handle, including typo-tolerant accent-stripped
 * matching.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;
use App\Services\SearchService;

new class extends Component {
    public $search = '';

    public function with()
    {
        $term = strtolower(trim($this->search));

        if (strlen($term) < 2) {
            return ['results' => []];
        }

        $results = Cache::remember("search_v4_{$term}", now()->addMinutes(15), fn () => app(SearchService::class)->search($term));

        return ['results' => $results];
    }
}; ?>

@php
    // Wraps the matching portion in a <mark> tag — safe: e() escapes user data before insertion.
    $highlight = function (string $text, string $term): string {
        $escaped = e($text);
        if (mb_strlen($term) < 2) return $escaped;
        return preg_replace(
            '/(' . preg_quote($term, '/') . ')/iu',
            '<mark class="bg-transparent text-[var(--brand-yellow)] font-black not-italic">$1</mark>',
            $escaped
        );
    };
@endphp

<div class="relative w-full max-w-md"
     x-data="{
         open: false,
         activeIndex: -1,
         recentSearches: [],

         init() {
             this.recentSearches = JSON.parse(localStorage.getItem('search_recent') || '[]');

             window.addEventListener('keydown', (e) => {
                 const tag = document.activeElement?.tagName;

                 if (e.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA') {
                     e.preventDefault();
                     this.focusSearch();
                 }
                 if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                     e.preventDefault();
                     this.focusSearch();
                 }

                 if (document.activeElement === this.$refs.searchInput) {
                     if (e.key === 'ArrowDown') { e.preventDefault(); this.moveDown(); }
                     else if (e.key === 'ArrowUp') { e.preventDefault(); this.moveUp(); }
                     else if (e.key === 'Enter') { e.preventDefault(); this.confirmSelection(); }
                     else if (e.key === 'Escape') { this.open = false; this.activeIndex = -1; this.$refs.searchInput.blur(); }
                 }
             });

             this.$el.addEventListener('livewire:updated', () => {
                 this.activeIndex = -1;
                 this.flatItems().forEach(el => {
                     el.style.backgroundColor = '';
                     el.style.borderLeftColor = '';
                     el.style.color = '';
                 });
             });
         },

         flatItems() {
             return [...this.$el.querySelectorAll('[data-search-item]')]
                 .filter(el => el.offsetParent !== null);
         },

         setActive(index) {
             this.flatItems().forEach((el, i) => {
                 const chevron = el.lastElementChild;
                 if (i === index) {
                     el.style.backgroundColor = 'rgba(255,255,255,0.05)';
                     el.style.borderLeftColor = 'var(--brand-yellow)';
                     el.style.color = 'white';
                     if (chevron) { chevron.style.opacity = '1'; chevron.style.transform = 'translateX(0)'; }
                 } else {
                     el.style.backgroundColor = '';
                     el.style.borderLeftColor = '';
                     el.style.color = '';
                     if (chevron) { chevron.style.opacity = ''; chevron.style.transform = ''; }
                 }
             });
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
             if (item) {
                 this.saveRecent(item.dataset.searchName, item.href, item.dataset.searchType);
                 window.location.href = item.href;
             } else if ($wire.search.length >= 2) {
                 window.location.href = '{{ route('search.results') }}?q=' + encodeURIComponent($wire.search);
             }
         },

         saveRecent(name, url, type) {
             const entries = JSON.parse(localStorage.getItem('search_recent') || '[]');
             const updated = [{ name, url, type }, ...entries.filter(r => r.url !== url)].slice(0, 5);
             localStorage.setItem('search_recent', JSON.stringify(updated));
             this.recentSearches = updated;
         },

         focusSearch() {
             this.$refs.searchInput.focus();
             this.$refs.searchInput.select();
             this.open = true;
         }
     }"
     @click.away="open = false; activeIndex = -1">

    <div class="group relative flex items-center">
        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none" aria-hidden="true">
            <x-fas-search class="w-3.5 h-3.5 inline-block text-gray-500 group-focus-within:text-[var(--brand-yellow)] transition-all" aria-hidden="true" />
        </div>

        <input type="search"
               wire:model.live.debounce.400ms="search"
               x-ref="searchInput"
               x-on:focus="open = true; activeIndex = -1"
               aria-label="{{ __('layout.searchbar') }}"
               role="combobox"
               :aria-expanded="(open && ($wire.search.length >= 2 || recentSearches.length > 0)).toString()"
               aria-autocomplete="list"
               aria-controls="search-results"
               autocomplete="off"
               class="block w-full py-2.5 pl-10 pr-16 text-xs font-bold tracking-[0.1em] rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-[var(--brand-yellow)]/40 focus:ring-1 focus:ring-[var(--brand-yellow)]/20 transition-all"
               placeholder="{{ __('layout.searchbar') }}">

        <div class="absolute right-3 hidden lg:flex items-center pointer-events-none select-none"
             x-show="!open" x-cloak>
            <kbd class="text-[8px] font-mono text-gray-600 border border-white/10 rounded px-1.5 py-0.5 leading-none tracking-widest">Ctrl K</kbd>
        </div>

        <div wire:loading class="absolute right-3" role="status" aria-label="{{ __('layout.searching') }}">
            <div class="w-3 h-3 border-2 border-[var(--brand-yellow)] border-t-transparent rounded-full animate-spin" aria-hidden="true"></div>
        </div>
    </div>

    <div x-show="open && $wire.search.length < 2 && recentSearches.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="absolute left-0 right-0 mt-2 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.8)] rounded-2xl bg-bg-main/95 backdrop-blur-3xl border border-white/10 z-[100] origin-top"
         x-cloak>

        <div class="px-4 py-2 bg-white/[0.02] border-b border-white/5 flex justify-between items-center">
            <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[var(--brand-yellow)]/80">
                // {{ __('layout.recent_searches') }}
            </span>
            <button x-on:click.stop="recentSearches = []; localStorage.removeItem('search_recent')"
                    class="text-[9px] text-gray-600 hover:text-gray-400 uppercase tracking-widest transition-colors">
                {{ __('layout.clear') }}
            </button>
        </div>

        <template x-for="recent in recentSearches" :key="recent.url">
            <a :href="recent.url"
               data-search-item
               :data-search-name="recent.name"
               :data-search-type="recent.type"
               x-on:click="saveRecent(recent.name, recent.url, recent.type)"
               class="flex items-center px-4 py-3 text-[11px] font-bold uppercase tracking-wider border-l-2 border-transparent text-gray-400 hover:bg-white/[0.03] hover:border-[var(--brand-yellow)] hover:text-white transition-all group outline-none">

                <div class="flex-shrink-0 w-8 h-5 flex items-center justify-center mr-3" aria-hidden="true">
                    <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                </div>

                <span x-text="recent.name" class="flex-1 truncate transition-colors"></span>
                <span x-text="'// ' + recent.type.replace(/s$/, '').toUpperCase()"
                      class="text-[8px] text-gray-600 tracking-widest ml-2 shrink-0 font-black"></span>
            </a>
        </template>
    </div>

    @if(!empty($results) && strlen($search) >= 2)
        <div x-show="open && $wire.search.length >= 2"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             id="search-results"
             role="listbox"
             aria-label="{{ __('layout.search_results') }}"
             class="absolute left-0 right-0 mt-2 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.8)] rounded-2xl bg-bg-main/95 backdrop-blur-3xl border border-white/10 z-[100] origin-top"
             x-cloak>

            @php $totalResults = 0; @endphp

            @foreach($results as $type => $items)
                @if(count($items) > 0)
                    @php $totalResults += count($items); @endphp
                    <div class="px-4 py-2 bg-white/[0.02] border-b border-white/5 flex justify-between items-center">
                        <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[var(--brand-yellow)]/80">
                            // {{ __("layout.type.".$type) }}
                        </span>
                    </div>

                    <div class="flex flex-col">
                        @foreach($items as $item)
                            @php $displayName = $item['handle'] ?? $item['name']; @endphp
                            <a href="{{ route($type . '.show', [$item['id'], str($displayName ?? '')->slug()]) }}"
                               role="option"
                               aria-selected="false"
                               data-search-item
                               data-search-name="{{ e($displayName) }}"
                               data-search-type="{{ $type }}"
                               x-on:click="saveRecent($el.dataset.searchName, $el.href, $el.dataset.searchType)"
                               class="flex items-center px-4 py-3 text-[11px] font-bold uppercase tracking-wider border-l-2 border-transparent text-gray-400 hover:bg-white/[0.03] hover:border-[var(--brand-yellow)] hover:text-white transition-all group outline-none border-b-0">

                                <div class="flex-shrink-0 w-8 h-5 flex items-center justify-center mr-3">
                                    @if($type === 'tournaments')
                                        <img src="{{ $item['logo'] }}"
                                             class="w-full h-full object-contain opacity-60 group-hover:opacity-100 transition-opacity"
                                             alt="{{ $item['name'] }}">

                                    @elseif($type === 'teams')
                                        @if($item['logo'] != asset('storage/images/default-team.webp'))
                                            <img src="{{ $item['logo'] }}"
                                                 class="w-full h-full object-contain"
                                                 alt="{{ $item['name'] }}">
                                        @else
                                            <span class="fi fi-{{ strtolower($item['country_code'] ?? 'un') }} fis rounded-[2px] opacity-80 group-hover:opacity-100 shadow-sm"
                                                  aria-label="{{ $item['country_code'] ?? '' }}"></span>
                                        @endif
                                    @else
                                        <span class="fi fi-{{ strtolower($item['country_code'] ?? 'un') }} fis rounded-[2px] opacity-80 group-hover:opacity-100 shadow-sm"
                                              aria-label="{{ $item['country_code'] ?? '' }}"></span>
                                    @endif
                                </div>

                                <div class="flex-1 truncate transition-colors">
                                    {!! $highlight($displayName, $search) !!}
                                </div>

                                <x-fas-chevron-right class="w-3 h-3 inline-block text-[var(--brand-yellow)] opacity-0 group-hover:opacity-100 transition-all transform -translate-x-2 group-hover:translate-x-0" aria-hidden="true" />
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach

            @if($totalResults === 0)
                <div class="px-4 py-10 text-center bg-transparent">
                    <div class="relative inline-block mb-3">
                        <x-fas-search class="w-6 h-6 inline-block text-gray-700" aria-hidden="true" />
                        <div class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                    </div>
                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-500">
                        {{ __("layout.noresult", ["research" => $search]) }}
                    </span>
                </div>
            @else
                <a href="{{ route('search.results', ['q' => $search]) }}"
                   class="flex items-center justify-center gap-2 px-4 py-3 text-[10px] font-black uppercase tracking-widest text-[var(--brand-yellow)] bg-white/[0.02] border-t border-white/5 hover:bg-white/[0.05] transition-all">
                    <span>{{ __('layout.see_more') }}</span>
                    <x-fas-arrow-right class="w-3 h-3 inline-block" aria-hidden="true" />
                </a>
            @endif
        </div>
    @endif
</div>
