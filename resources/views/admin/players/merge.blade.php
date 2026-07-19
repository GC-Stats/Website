{{--
    GC-Stats — Admin: merge player

    Picks a target player (the survivor — its profile fields are kept as-is)
    and, per data category, exactly which items of $player's (the source)
    data to move into it. $player itself is never deleted here. Mirrors
    admin/teams/merge.blade.php, scoped to what a Player actually owns (see
    PlayerMergeService's docblock): team history, news, logos and matches
    (game_player_stats rows) — no roster/tournaments/roles categories,
    players don't have those. Matches are grouped by tournament for
    display/bulk-select only — selection is still per match row, unlike the
    team merge's per-tournament-only selection (see mergeStats() below).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.players.merge.title'))

@section('content')
    <a href="{{ route('admin.players.show', $player) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $player->handle }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">
        {{ __('admin.players.merge.title') }}
    </h1>

    @error('target_id')
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3 mb-6">{{ $message }}</div>
    @enderror

    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-2">{{ __('admin.players.merge.select_target') }}</p>

            <form method="GET" action="{{ route('admin.players.merge.show', $player) }}" class="flex gap-2 mb-4">
                <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.players.merge.target_search_placeholder') }}"
                       class="flex-1 max-w-sm bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                <button type="submit"
                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                    {{ __('admin.players.merge.target_search_submit') }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.players.merge.execute', $player) }}"
                  x-data="{
                      targetName: '',
                      teams: [],
                      news: [],
                      logos: [],
                      stats: [],
                      total() { return this.teams.length + this.news.length + this.logos.length + this.stats.length; },
                  }">
                @csrf

                <div class="space-y-6">
                @if ($search)
                    <div class="space-y-2">
                        @forelse ($searchResults as $found)
                            <label class="flex items-center gap-3 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 cursor-pointer">
                                <input type="radio" name="target_id" value="{{ $found->id }}" required
                                       @change="targetName = '{{ addslashes($found->handle) }}'"
                                       class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                <span class="text-sm text-white font-semibold">{{ $found->handle }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-gray-500">{{ __('admin.players.merge.target_search_empty') }}</p>
                        @endforelse
                    </div>
                @endif

                {{-- Teams --}}
                @if ($teamItems->isNotEmpty())
                    <div class="pt-4 border-t border-border-subtle space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="teams.length === {{ $teamItems->count() }}"
                                   @change="teams = $event.target.checked ? @js($teamItems->pluck('id')->map(fn ($id) => (string) $id)) : []"
                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.players.merge.categories.teams') }}</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($teamItems as $entry)
                                <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 cursor-pointer">
                                    <input type="checkbox" name="teams[]" value="{{ $entry->id }}" x-model="teams"
                                           class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                    {{ $entry->team_name }}
                                    @if ($entry->role && $entry->role !== 'player')
                                        <span class="text-gray-500">({{ $entry->role }})</span>
                                    @endif
                                    <span class="text-gray-600 text-xs">&mdash; {{ __('admin.players.merge.team_joined', ['date' => $entry->joined_at]) }}</span>
                                    @if ($entry->left_at)
                                        <span class="text-gray-600 text-xs">&mdash; {{ __('admin.players.merge.team_left', ['date' => $entry->left_at]) }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- News --}}
                @if ($newsItems->isNotEmpty())
                    <div class="pt-4 border-t border-border-subtle space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="news.length === {{ $newsItems->count() }}"
                                   @change="news = $event.target.checked ? @js($newsItems->pluck('id')->map(fn ($id) => (string) $id)) : []"
                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.players.merge.categories.news') }}</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($newsItems as $article)
                                <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 cursor-pointer">
                                    <input type="checkbox" name="news[]" value="{{ $article->id }}" x-model="news"
                                           class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                    {{ $article->title }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Logos --}}
                @if ($logoItems->isNotEmpty())
                    <div class="pt-4 border-t border-border-subtle space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="logos.length === {{ $logoItems->count() }}"
                                   @change="logos = $event.target.checked ? @js($logoItems->pluck('id')) : []"
                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.players.merge.categories.logos') }}</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($logoItems as $logo)
                                <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 cursor-pointer">
                                    <input type="checkbox" name="logos[]" value="{{ $logo->id }}" x-model="logos"
                                           class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                    {{ $logo->from?->format('Y-m-d') }}
                                    @if ($logo->until)
                                        &rarr; {{ $logo->until->format('Y-m-d') }}
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Matches, grouped by tournament --}}
                @if ($matchGroups->isNotEmpty())
                    @php $allMatchIds = $matchGroups->flatten(1)->pluck('id')->map(fn ($id) => (string) $id); @endphp
                    <div class="pt-4 border-t border-border-subtle space-y-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="stats.length === {{ $allMatchIds->count() }}"
                                   @change="stats = $event.target.checked ? @js($allMatchIds) : []"
                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.players.merge.categories.matches') }}</span>
                        </label>

                        @foreach ($matchGroups as $tournamentMatches)
                            @php $tournamentMatchIds = $tournamentMatches->pluck('id')->map(fn ($id) => (string) $id); @endphp
                            <div class="pl-4 border-l-2 border-border-subtle space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           :checked="{{ json_encode($tournamentMatchIds) }}.every(id => stats.includes(id))"
                                           @change="stats = $event.target.checked
                                               ? [...new Set([...stats, ...{{ json_encode($tournamentMatchIds) }}])]
                                               : stats.filter(id => !{{ json_encode($tournamentMatchIds) }}.includes(id))"
                                           class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                    <span class="text-xs font-bold text-white">{{ $tournamentMatches->first()->tournament_name }}</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($tournamentMatches as $stat)
                                        <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 cursor-pointer">
                                            <input type="checkbox" name="stats[]" value="{{ $stat->id }}" x-model="stats"
                                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                            {{ $stat->agent_name }}
                                            <span class="text-gray-600 text-xs">&mdash; {{ $stat->scheduled_at }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                </div>

                <div class="pt-6">
                    <x-confirm-modal
                        :title="__('admin.players.merge.confirm_title')"
                        :body="__('admin.players.merge.confirm_body', ['source' => $player->handle])"
                        :trigger-label="__('admin.players.merge.submit')"
                        :submit-label="__('admin.players.merge.submit')"
                        trigger-class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90"
                        submit-class="bg-gc-yellow text-black hover:opacity-90"
                    >
                        <p class="text-xs text-gray-400">
                            <span x-text="targetName || '{{ __('admin.players.merge.no_target_selected') }}'"></span>
                            &mdash;
                            <span x-text="total() ? total() + ' {{ __('admin.players.merge.items_selected') }}' : '{{ __('admin.players.merge.categories_none') }}'"></span>
                        </p>
                    </x-confirm-modal>
                </div>
            </form>
        </div>
    </div>
@endsection
