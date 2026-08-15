<?php

/**
 * GC-Stats — Forum embed picker Livewire component
 *
 * Search-and-insert popover for the forum composer's embed button — the
 * "instead of a screenshot, link the match/player/team" feature. Player and
 * team search reuse App\Services\SearchService::searchEntities() (same
 * engine as the entity-picker component); match search is bespoke since
 * matches have no own "name" column to search — it matches either team's
 * name instead.
 *
 * Player/team ("header"/"stats") and match flows differ in step order.
 * Player/team: pick the variant up front (toggle above the search box),
 * then a result — "header" dispatches immediately, "stats" moves to a
 * filter step (agent, period, tournament — see buildFilterQuery()).
 * Match doesn't ask for a variant until *after* a match is picked (there's
 * nothing to toggle before you know which match): search/browse has no
 * variant row at all, picking a result always opens a variant-choice step,
 * and only "player" needs a third step (the two rosters, see
 * matchRosterFor()) — every other match variant dispatches as soon as it's
 * chosen. See pick()/chooseMatchVariant()/pickMatchPlayer().
 *
 * Dispatches {type, id, label, variant, query} so the composer (resources/
 * views/livewire/forum-thread.blade.php) can insert a `{{type:id}}`,
 * `{{type:id:variant}}`, or `{{type:id:variant?query}}` reference — see
 * App\Models\ForumMessage::parseBody() for how that gets parsed back out.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\SearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    private const TYPES = ['player', 'team', 'match'];

    private const VARIANTS_BY_TYPE = [
        'player' => ['header', 'stats'],
        'team' => ['header', 'stats'],
        'match' => ['header', 'scoreboard', 'performance', 'economy', 'player'],
    ];

    private const PERIODS = ['7d', '30d', '90d', 'all', 'custom'];

    public string $type = 'player';

    public string $variant = 'header';

    public string $search = '';

    public string $eventName = 'embed-selected';

    // Filter step (variant === 'stats' only) — null selectedId means
    // "still browsing search results", not yet on the filter step.
    public ?int $selectedId = null;

    public string $selectedLabel = '';

    public string $agent = '';

    public string $period = 'all';

    public string $customFrom = '';

    public string $customTo = '';

    public string $tournamentSearch = '';

    public ?int $tournamentId = null;

    public string $tournamentLabel = '';

    // Match's "player" variant only — which roster member was picked on
    // the second step (see matchRosterFor()).
    public ?int $matchPlayerId = null;

    public function mount(?string $eventName = null): void
    {
        $this->eventName = $eventName ?? 'embed-selected';
    }

    public function setType(string $type): void
    {
        if (in_array($type, self::TYPES, true)) {
            $this->type = $type;
            $this->search = '';
            // A variant carried over from the previous type isn't
            // necessarily valid for this one (e.g. 'scoreboard' only makes
            // sense for 'match') — reset to the always-valid default.
            $this->variant = 'header';
            $this->resetFilters();
        }
    }

    public function setVariant(string $variant): void
    {
        if (in_array($variant, self::VARIANTS_BY_TYPE[$this->type] ?? [], true)) {
            $this->variant = $variant;
            $this->resetFilters();
        }
    }

    public function setPeriod(string $period): void
    {
        if (in_array($period, self::PERIODS, true)) {
            $this->period = $period;
        }
    }

    public function pickTournament(int $id, string $label): void
    {
        $this->tournamentId = $id;
        $this->tournamentLabel = $label;
        $this->tournamentSearch = '';
    }

    public function clearTournament(): void
    {
        $this->tournamentId = null;
        $this->tournamentLabel = '';
    }

    /**
     * Entry point for clicking a search result. Match always opens the
     * variant-choice step next (see chooseMatchVariant()) — resetting
     * $variant to 'header' here is what that step reads as "not chosen
     * yet" (confirm() clears $selectedId right after a variant other than
     * 'player' is picked, so this is the only path that can leave
     * $selectedId set with $variant === 'header'). Player/team either
     * insert right away ("header") or open the stats filter step.
     */
    public function pick(int $id, string $label): void
    {
        if ($this->type === 'match') {
            $this->selectedId = $id;
            $this->selectedLabel = $label;
            $this->variant = 'header';

            return;
        }

        if ($this->variant !== 'stats') {
            $this->confirm($id, $label);

            return;
        }

        $this->selectedId = $id;
        $this->selectedLabel = $label;
    }

    /**
     * Match's variant-choice step (after a match is picked, see pick()).
     * Every variant except "player" has nothing left to configure and
     * dispatches immediately; "player" instead moves on to the roster step
     * (matchRosterFor(), rendered once $variant === 'player').
     */
    public function chooseMatchVariant(string $variant): void
    {
        if (! in_array($variant, self::VARIANTS_BY_TYPE['match'], true)) {
            return;
        }

        $this->variant = $variant;

        if ($variant !== 'player') {
            $this->confirm($this->selectedId, $this->selectedLabel);
        }
    }

    /**
     * Match "player" variant's roster step — picking a player dispatches
     * immediately (no separate "Insert" the way stats filters have, there's
     * nothing else to configure).
     */
    public function pickMatchPlayer(int $playerId, string $label): void
    {
        $this->matchPlayerId = $playerId;
        $this->confirm($this->selectedId, $this->selectedLabel.' — '.$label);
    }

    public function backToSearch(): void
    {
        $this->selectedId = null;
        $this->selectedLabel = '';
        $this->resetFilters();
    }

    public function confirmWithFilters(): void
    {
        if ($this->selectedId !== null) {
            $this->confirm($this->selectedId, $this->selectedLabel);
        }
    }

    private function confirm(int $id, string $label): void
    {
        $this->dispatch(
            $this->eventName,
            type: $this->type,
            id: $id,
            label: $label,
            variant: $this->type === 'match' && $this->variant === 'header' ? null : $this->variant,
            query: $this->buildFilterQuery(),
        );

        $this->selectedId = null;
        $this->selectedLabel = '';
        $this->resetFilters();
    }

    private function buildFilterQuery(): ?string
    {
        if ($this->type === 'match' && $this->variant === 'player') {
            return $this->matchPlayerId !== null ? http_build_query(['player' => $this->matchPlayerId]) : null;
        }

        if ($this->variant !== 'stats') {
            return null;
        }

        $params = [];

        if ($this->type === 'player' && trim($this->agent) !== '') {
            $params['agent'] = $this->agent;
        }

        if ($this->period === 'custom') {
            if ($this->customFrom !== '') {
                $params['from'] = $this->customFrom;
            }
            if ($this->customTo !== '') {
                $params['to'] = $this->customTo;
            }
        } elseif ($this->period !== 'all') {
            $params['period'] = $this->period;
        }

        if ($this->tournamentId !== null) {
            $params['tournament'] = $this->tournamentId;
        }

        return $params === [] ? null : http_build_query($params);
    }

    private function resetFilters(): void
    {
        $this->agent = '';
        $this->period = 'all';
        $this->customFrom = '';
        $this->customTo = '';
        $this->tournamentSearch = '';
        $this->tournamentId = null;
        $this->tournamentLabel = '';
        $this->matchPlayerId = null;
    }

    /**
     * The two rosters for match's "player" variant's picker step — every
     * player who appears in game_player_stats for this match, split by
     * team via the same majority-team_id logic App\Services\
     * MatchStatsService uses for the embed itself.
     *
     * @return array{team_a: list<array{id: int, handle: string}>, team_b: list<array{id: int, handle: string}>}
     */
    private function matchRosterFor(int $matchId): array
    {
        $match = Matchs::find($matchId);

        if (! $match) {
            return ['team_a' => [], 'team_b' => []];
        }

        $rows = DB::table('game_player_stats')
            ->join('players', 'game_player_stats.player_id', '=', 'players.id')
            ->where('game_player_stats.match_id', $matchId)
            ->select(['game_player_stats.player_id', 'game_player_stats.team_id', 'players.handle'])
            ->distinct()
            ->get()
            ->groupBy('player_id');

        $roster = ['team_a' => [], 'team_b' => []];

        foreach ($rows as $playerId => $playerRows) {
            $majorityTeamId = $playerRows->countBy('team_id')->sortDesc()->keys()->first();
            $key = $majorityTeamId == $match->team_a_id ? 'team_a' : ($majorityTeamId == $match->team_b_id ? 'team_b' : null);

            if ($key !== null) {
                $roster[$key][] = ['id' => (int) $playerId, 'handle' => $playerRows->first()->handle];
            }
        }

        return $roster;
    }

    /**
     * @return list<array{id: int, title: string, subtitle: ?string}>
     */
    private function searchMatches(): array
    {
        $term = trim($this->search);

        return Matchs::with(['teamA:id,name', 'teamB:id,name', 'tournament:id,name'])
            ->when($term !== '', fn ($query) => $query->where(function ($q) use ($term) {
                $q->whereHas('teamA', fn ($t) => $t->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('teamB', fn ($t) => $t->where('name', 'like', "%{$term}%"));
            }))
            ->latest('scheduled_at')
            // Browsing (no term) is capped tighter than an actual search —
            // "latest 5 matches site-wide" is a starting point to scroll
            // from, not a real result set the way a name match is.
            ->limit($term === '' ? 5 : 8)
            ->get()
            ->map(fn (Matchs $match) => [
                'id' => $match->id,
                'title' => ($match->teamA?->name ?? '?').' vs '.($match->teamB?->name ?? '?'),
                'subtitle' => $match->tournament?->name,
            ])
            ->all();
    }

    /**
     * Browsing (empty search box) for player/team ranks by the same
     * page-view popularity signal the main site search uses (see
     * SearchService::search()) instead of alphabetically — "most searched"
     * is a more useful default than "A" names first for a list capped at 5.
     * There's no denormalized popularity column to sort on directly, so
     * this aggregates the last 30 days of `page_views` for the type's
     * route prefix, extracts the entity id from each stored URI (always
     * `/player/{id}[...]` or `/team/{id}[...], id first), and sums by id
     * (a renamed entity's page views under an old slug still count).
     *
     * @return list<array{id: int, title: string, subtitle: ?string}>
     */
    private function popularEntities(string $type): array
    {
        return Cache::remember("forum.embed_picker.popular.{$type}", now()->addMinutes(10), fn () => $this->popularEntitiesUncached($type));
    }

    /**
     * @return list<array{id: int, title: string, subtitle: ?string}>
     */
    private function popularEntitiesUncached(string $type): array
    {
        $prefix = $type === 'player' ? '/player/' : '/team/';

        $ids = DB::table('page_views')
            ->where('uri', 'like', $prefix.'%')
            ->where('viewed_at', '>=', now()->subDays(30))
            ->select('uri', DB::raw('SUM(count) as total'))
            ->groupBy('uri')
            ->get()
            ->map(function ($row) use ($prefix) {
                preg_match('#^'.preg_quote($prefix, '#').'(\d+)#', $row->uri, $m);

                return $m ? ['id' => (int) $m[1], 'total' => $row->total] : null;
            })
            ->filter()
            ->groupBy('id')
            ->map(fn ($rows) => $rows->sum('total'))
            ->sortDesc()
            ->take(5)
            ->keys();

        if ($ids->isEmpty()) {
            return [];
        }

        $modelClass = $type === 'player' ? Player::class : Team::class;
        $models = $modelClass::whereIn('id', $ids)->get()->keyBy('id');

        return $ids
            ->map(fn ($id) => $models->get($id))
            ->filter()
            ->map(fn ($model) => $type === 'player'
                ? ['id' => $model->id, 'title' => $model->handle, 'subtitle' => null]
                : ['id' => $model->id, 'title' => $model->name, 'subtitle' => $model->short_name])
            ->values()
            ->all();
    }

    public function with(): array
    {
        $empty = ['results' => [], 'agents' => [], 'tournamentResults' => [], 'matchRoster' => ['team_a' => [], 'team_b' => []]];

        // On the second step, the search box/result list aren't shown at
        // all — no need to run the entity search while configuring.
        if ($this->selectedId !== null) {
            if ($this->type === 'match' && $this->variant === 'player') {
                return [...$empty, 'matchRoster' => $this->matchRosterFor($this->selectedId)];
            }

            $agents = $this->type === 'player'
                ? DB::table('game_player_stats')->where('player_id', $this->selectedId)->distinct()->orderBy('agent_name')->pluck('agent_name')->all()
                : [];

            $tournamentResults = trim($this->tournamentSearch) !== ''
                ? Tournament::where('name', 'like', '%'.trim($this->tournamentSearch).'%')->orderBy('name')->limit(6)->get(['id', 'name'])->all()
                : [];

            return [...$empty, 'agents' => $agents, 'tournamentResults' => $tournamentResults];
        }

        if ($this->type === 'match') {
            return [...$empty, 'results' => $this->searchMatches()];
        }

        $term = trim($this->search);

        $results = $term === ''
            ? $this->popularEntities($this->type)
            : collect(app(SearchService::class)->searchEntities($this->type, $term, 8))
                ->map(fn (array $item) => ['id' => $item['id'], 'title' => $item['title'], 'subtitle' => $item['subtitle']])
                ->all();

        return [...$empty, 'results' => $results];
    }
}; ?>

<div class="w-72 bg-bg-main border border-white/10 rounded-xl shadow-2xl overflow-hidden">
    @if ($selectedId !== null)
        {{-- Filter step --}}
        <div class="flex items-center gap-2 p-2 border-b border-white/5">
            <button type="button" wire:click="backToSearch" class="text-gray-500 hover:text-white transition shrink-0" title="{{ __('forum.embed.filters.back') }}">
                @svg('fas-arrow-left', 'w-3 h-3', ['aria-hidden' => 'true'])
            </button>
            <span class="text-xs font-black text-white truncate">{{ $selectedLabel }}</span>
        </div>

        <div class="p-3 space-y-3 max-h-80 overflow-y-auto">
            @if ($type === 'match' && $variant === 'header')
                {{-- Variant-choice step — picking anything but "player" dispatches immediately (see chooseMatchVariant()) --}}
                <div class="grid grid-cols-2 gap-1">
                    @foreach (['header', 'scoreboard', 'performance', 'economy', 'player'] as $option)
                        <button type="button" wire:click="chooseMatchVariant('{{ $option }}')"
                                class="py-2 text-[10px] font-bold uppercase tracking-widest rounded-lg transition bg-white/5 text-gray-300 hover:bg-gc-yellow hover:text-black">
                            {{ __('forum.embed.variant.'.$option) }}
                        </button>
                    @endforeach
                </div>
            @elseif ($type === 'match' && $variant === 'player')
                @foreach (['team_a' => __('forum.embed.filters.roster_team_a'), 'team_b' => __('forum.embed.filters.roster_team_b')] as $key => $teamLabel)
                    <div>
                        <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">{{ $teamLabel }}</label>
                        <div class="space-y-1">
                            @forelse ($matchRoster[$key] as $player)
                                <button type="button" wire:click="pickMatchPlayer({{ $player['id'] }}, '{{ addslashes($player['handle']) }}')"
                                        class="block w-full text-left px-2 py-1.5 text-xs text-white bg-white/5 hover:bg-white/10 rounded-lg transition truncate">
                                    {{ $player['handle'] }}
                                </button>
                            @empty
                                <p class="text-[10px] text-gray-600 italic">—</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            @else
            @if ($type === 'player')
                <div>
                    <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">{{ __('forum.embed.filters.agent_label') }}</label>
                    <select wire:model="agent" class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        <option value="">{{ __('forum.embed.filters.all_agents') }}</option>
                        @foreach ($agents as $agentName)
                            <option value="{{ $agentName }}">{{ $agentName }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">{{ __('forum.embed.filters.period_label') }}</label>
                <div class="grid grid-cols-2 gap-1">
                    @foreach (['all', '7d', '30d', '90d', 'custom'] as $option)
                        <button type="button" wire:click="setPeriod('{{ $option }}')"
                                class="py-1.5 text-[9px] font-bold uppercase tracking-widest rounded-md transition {{ $period === $option ? 'bg-gc-yellow text-black' : 'bg-white/5 text-gray-400 hover:text-white' }}">
                            {{ __('forum.embed.period.'.$option) }}
                        </button>
                    @endforeach
                </div>

                @if ($period === 'custom')
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div>
                            <label class="block text-[9px] text-gray-500 mb-1">{{ __('forum.embed.filters.from_label') }}</label>
                            <input type="date" wire:model="customFrom" class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                        <div>
                            <label class="block text-[9px] text-gray-500 mb-1">{{ __('forum.embed.filters.to_label') }}</label>
                            <input type="date" wire:model="customTo" class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">{{ __('forum.embed.filters.tournament_label') }}</label>

                @if ($tournamentId !== null)
                    <div class="flex items-center justify-between gap-2 bg-white/5 border border-white/10 rounded-lg px-2 py-1.5">
                        <span class="text-xs text-white truncate">{{ $tournamentLabel }}</span>
                        <button type="button" wire:click="clearTournament" class="text-gray-500 hover:text-white transition shrink-0">
                            @svg('fas-xmark', 'w-3 h-3', ['aria-hidden' => 'true'])
                        </button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="tournamentSearch" autocomplete="off"
                           placeholder="{{ __('forum.embed.filters.tournament_search_placeholder') }}"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition">

                    @if (count($tournamentResults) > 0)
                        <div class="mt-1 border border-white/10 rounded-lg overflow-hidden">
                            @foreach ($tournamentResults as $tournament)
                                <button type="button" wire:click="pickTournament({{ $tournament->id }}, '{{ addslashes($tournament->name) }}')"
                                        class="block w-full text-left px-2 py-1.5 text-xs text-white hover:bg-white/5 transition truncate">
                                    {{ $tournament->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <button type="button" wire:click="confirmWithFilters"
                    class="w-full font-bold uppercase text-[10px] tracking-widest py-2 rounded-lg transition active:scale-95 bg-gc-yellow/10 border border-gc-yellow/40 text-gc-yellow hover:bg-gc-yellow/20">
                {{ __('forum.embed.filters.apply') }}
            </button>
            @endif
        </div>
    @else
        {{-- Search step --}}
        <div class="flex border-b border-white/5">
            @foreach (['player', 'team', 'match'] as $option)
                <button type="button" wire:click="setType('{{ $option }}')"
                        class="flex-1 py-2 text-[10px] font-bold uppercase tracking-widest transition {{ $type === $option ? 'text-gc-yellow border-b-2 border-gc-yellow' : 'text-gray-500 hover:text-white' }}">
                    {{ __('forum.embed.type.'.$option) }}
                </button>
            @endforeach
        </div>

        @if ($type !== 'match')
            {{-- Match asks for a variant after picking a match instead (see the variant-choice step above) — nothing to choose here yet. --}}
            <div class="flex gap-1 p-2 border-b border-white/5">
                @foreach (['header', 'stats'] as $option)
                    <button type="button" wire:click="setVariant('{{ $option }}')"
                            class="flex-1 py-1.5 text-[9px] font-bold uppercase tracking-widest rounded-md transition {{ $variant === $option ? 'bg-gc-yellow text-black' : 'bg-white/5 text-gray-400 hover:text-white' }}">
                        {{ __('forum.embed.variant.'.$option) }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="relative p-2 border-b border-white/5">
            <input type="text" wire:model.live.debounce.300ms="search" autocomplete="off"
                   placeholder="{{ __('forum.embed.search_placeholder') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 pr-8 text-xs text-white focus:outline-none focus:border-gc-yellow transition">

            <div wire:loading.delay class="absolute right-4 top-1/2 -translate-y-1/2" role="status">
                <div class="w-3 h-3 border-2 border-gc-yellow border-t-transparent rounded-full animate-spin" aria-hidden="true"></div>
            </div>
        </div>

        <div class="max-h-56 overflow-y-auto" wire:loading.class="opacity-50">
            @forelse ($results as $result)
                <button type="button" wire:click="pick({{ $result['id'] }}, '{{ addslashes($result['title']) }}')"
                        class="block w-full text-left px-3 py-2 hover:bg-white/5 transition">
                    <span class="block text-xs font-bold text-white truncate">{{ $result['title'] }}</span>
                    @if ($result['subtitle'])
                        <span class="block text-[10px] text-gray-500 truncate">{{ $result['subtitle'] }}</span>
                    @endif
                </button>
            @empty
                <p class="text-center text-xs text-gray-500 py-4">{{ __('forum.embed.no_results') }}</p>
            @endforelse
        </div>
    @endif
</div>
