<?php

/**
 * GC-Stats — Team fan picker Livewire component
 *
 * Search-as-you-type team combobox (same look and typo-tolerant ranking as
 * the global searchbar, see App\Services\SearchService::searchTeams()) used
 * on the account settings page to pick a "fan of" team + one of its tags.
 * Renders plain `team_id`/`team_tag` hidden inputs so the surrounding native
 * <form> (Auth\AccountSettingsController::updateFanTeam) submits them as-is
 * — this component only manages the picking UI, not the save itself.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use Livewire\Volt\Component;
use App\Models\Team;
use App\Services\SearchService;

new class extends Component {
    public string $search = '';

    public ?int $selectedTeamId = null;

    public ?string $selectedTeamName = null;

    public ?string $selectedTeamLogo = null;

    public ?string $selectedTeamCountryCode = null;

    /** @var list<string> */
    public array $selectedTeamTags = [];

    public ?string $selectedTeamTag = null;

    public function mount(?int $initialTeamId = null, ?string $initialTeamTag = null): void
    {
        if ($initialTeamId === null) {
            return;
        }

        $team = Team::find($initialTeamId);

        if ($team === null) {
            return;
        }

        $this->selectedTeamId = $team->id;
        $this->selectedTeamName = $team->name;
        $this->selectedTeamLogo = $team->logo;
        $this->selectedTeamCountryCode = $team->country_code;
        $this->selectedTeamTags = $team->fanTags();
        $this->selectedTeamTag = $initialTeamTag;
    }

    public function selectTeam(int $teamId): void
    {
        $team = Team::findOrFail($teamId);

        $this->selectedTeamId = $team->id;
        $this->selectedTeamName = $team->name;
        $this->selectedTeamLogo = $team->logo;
        $this->selectedTeamCountryCode = $team->country_code;
        $this->selectedTeamTags = $team->fanTags();
        $this->selectedTeamTag = null;
        $this->search = '';
    }

    public function clearTeam(): void
    {
        $this->selectedTeamId = null;
        $this->selectedTeamName = null;
        $this->selectedTeamLogo = null;
        $this->selectedTeamCountryCode = null;
        $this->selectedTeamTags = [];
        $this->selectedTeamTag = null;
    }

    public function with(): array
    {
        $term = trim($this->search);

        return [
            'results' => strlen($term) >= 2 ? app(SearchService::class)->searchTeams($term) : [],
        ];
    }
}; ?>

<div class="space-y-3" x-data="{ open: false }" @click.away="open = false">
    <input type="hidden" name="team_id" value="{{ $selectedTeamId }}">

    @if ($selectedTeamId)
        <div class="flex items-center justify-between gap-3 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="flex-shrink-0 w-8 h-5 flex items-center justify-center">
                    @if ($selectedTeamLogo && $selectedTeamLogo !== asset('storage/images/default-team.webp'))
                        <img src="{{ $selectedTeamLogo }}" class="w-full h-full object-contain" alt="{{ $selectedTeamName }}">
                    @else
                        <span class="fi fi-{{ strtolower($selectedTeamCountryCode ?? 'un') }} fis rounded-[2px] shadow-sm" aria-label="{{ $selectedTeamCountryCode }}"></span>
                    @endif
                </div>
                <span class="text-sm font-bold uppercase tracking-wider text-white truncate">{{ $selectedTeamName }}</span>
            </div>
            <button type="button" wire:click="clearTeam"
                    class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-3 py-2 rounded-sm transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                {{ __('account.edit.team.remove') }}
            </button>
        </div>

        @if (count($selectedTeamTags) > 0)
            <div>
                <label for="selected_team_tag" class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('account.edit.team.tag_label') }}
                </label>
                <select id="selected_team_tag" name="team_tag"
                        class="w-full bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    <option value="">{{ __('account.edit.team.tag_none') }}</option>
                    @foreach ($selectedTeamTags as $tag)
                        <option value="{{ $tag }}" @selected($tag === $selectedTeamTag)>{{ $tag }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <p class="text-xs text-gray-500">{{ __('account.edit.team.no_tags') }}</p>
        @endif
    @else
        <div class="relative">
            <div class="group relative flex items-center">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none" aria-hidden="true">
                    @svg('fas-search', 'w-3.5 h-3.5 inline-block text-gray-500 group-focus-within:text-gc-yellow transition-all', ['aria-hidden' => 'true'])
                </div>
                <input type="search"
                       wire:model.live.debounce.400ms="search"
                       x-on:focus="open = true"
                       autocomplete="off"
                       placeholder="{{ __('account.edit.team.search_placeholder') }}"
                       class="block w-full py-2.5 pl-10 pr-4 text-xs font-bold tracking-[0.1em] rounded-sm bg-[#050505] border border-border-subtle text-white placeholder-gray-500 focus:outline-none focus:border-gc-yellow transition-all">
                <div wire:loading class="absolute right-3" role="status">
                    <div class="w-3 h-3 border-2 border-gc-yellow border-t-transparent rounded-full animate-spin" aria-hidden="true"></div>
                </div>
            </div>

            @if (strlen(trim($search)) >= 2)
                <div x-show="open"
                     class="absolute left-0 right-0 mt-2 overflow-hidden shadow-xl rounded-sm bg-bg-card border border-border-subtle z-20"
                     x-cloak>
                    @forelse ($results as $item)
                        <button type="button" wire:click="selectTeam({{ $item['id'] }})"
                                class="w-full flex items-center px-4 py-3 text-[11px] font-bold uppercase tracking-wider border-l-2 border-transparent text-gray-400 hover:bg-white/[0.03] hover:border-gc-yellow hover:text-white transition-all">
                            <div class="flex-shrink-0 w-8 h-5 flex items-center justify-center mr-3">
                                @if ($item['logo'] !== asset('storage/images/default-team.webp'))
                                    <img src="{{ $item['logo'] }}" class="w-full h-full object-contain" alt="{{ $item['name'] }}">
                                @else
                                    <span class="fi fi-{{ strtolower($item['country_code'] ?? 'un') }} fis rounded-[2px] shadow-sm" aria-label="{{ $item['country_code'] }}"></span>
                                @endif
                            </div>
                            <span class="flex-1 truncate text-left">{{ $item['name'] }}</span>
                        </button>
                    @empty
                        <p class="px-4 py-3 text-xs text-gray-500">{{ __('account.edit.team.search_empty') }}</p>
                    @endforelse
                </div>
            @endif
        </div>
    @endif
</div>
