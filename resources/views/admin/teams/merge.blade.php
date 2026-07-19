{{--
    GC-Stats — Admin: merge team

    Picks a target team (the survivor — its profile fields are kept as-is)
    and, per data category, exactly which items of $team's (the source)
    data to move into it. $team itself is never deleted here. There is no
    standalone "matches" category — checking a tournament also transfers
    $team's matches within it (see TeamMergeService::mergeTournaments()).
    Roles move as (role, user) pairs — the role assignment on $team is
    replaced by the equivalent role on the target, not the role row itself
    (see TeamMergeService::mergeRoles()).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.teams.merge.title'))

@section('content')
    <a href="{{ route('admin.teams.show', $team) }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $team->name }}
    </a>

    <h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">
        {{ __('admin.teams.merge.title') }}
    </h1>

    @error('target_id')
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3 mb-6">{{ $message }}</div>
    @enderror

    <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-6">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-2">{{ __('admin.teams.merge.select_target') }}</p>

            <form method="GET" action="{{ route('admin.teams.merge.show', $team) }}" class="flex gap-2 mb-4">
                <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.teams.merge.target_search_placeholder') }}"
                       class="flex-1 max-w-sm bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                <button type="submit"
                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-sm transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                    {{ __('admin.teams.merge.target_search_submit') }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.teams.merge.execute', $team) }}"
                  x-data="{
                      targetName: '',
                      roster: [],
                      tournaments: [],
                      news: [],
                      logos: [],
                      roles: [],
                      total() { return this.roster.length + this.tournaments.length + this.news.length + this.logos.length + this.roles.length; },
                  }">
                @csrf

                <div class="space-y-6">
                @if ($search)
                    <div class="space-y-2">
                        @forelse ($searchResults as $found)
                            <label class="flex items-center gap-3 bg-[#050505] border border-border-subtle rounded-sm px-4 py-3 cursor-pointer">
                                <input type="radio" name="target_id" value="{{ $found->id }}" required
                                       @change="targetName = '{{ addslashes($found->name) }}'"
                                       class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                <span class="text-sm text-white font-semibold">{{ $found->name }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-gray-500">{{ __('admin.teams.merge.target_search_empty') }}</p>
                        @endforelse
                    </div>
                @endif

                {{-- Roster --}}
                @if ($rosterItems->isNotEmpty())
                    <div class="pt-4 border-t border-border-subtle space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="roster.length === {{ $rosterItems->count() }}"
                                   @change="roster = $event.target.checked ? @js($rosterItems->pluck('id')->map(fn ($id) => (string) $id)) : []"
                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.teams.merge.categories.roster') }}</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($rosterItems as $entry)
                                <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 cursor-pointer">
                                    <input type="checkbox" name="roster[]" value="{{ $entry->id }}" x-model="roster"
                                           class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                    {{ $entry->player_handle }}
                                    @if ($entry->role && $entry->role !== 'player')
                                        <span class="text-gray-500">({{ $entry->role }})</span>
                                    @endif
                                    <span class="text-gray-600 text-xs">&mdash; {{ __('admin.teams.merge.roster_joined', ['date' => $entry->joined_at]) }}</span>
                                    @if ($entry->left_at)
                                        <span class="text-gray-600 text-xs">&mdash; {{ __('admin.teams.merge.roster_left', ['date' => $entry->left_at]) }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tournaments (matches ride along, see TeamMergeService) --}}
                @if ($tournamentItems->isNotEmpty())
                    <div class="pt-4 border-t border-border-subtle space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="tournaments.length === {{ $tournamentItems->count() }}"
                                   @change="tournaments = $event.target.checked ? @js($tournamentItems->pluck('id')->map(fn ($id) => (string) $id)) : []"
                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.teams.merge.categories.tournaments') }}</span>
                            <span class="text-[10px] text-gray-600">{{ __('admin.teams.merge.tournaments_hint') }}</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($tournamentItems as $tournament)
                                <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 cursor-pointer">
                                    <input type="checkbox" name="tournaments[]" value="{{ $tournament->id }}" x-model="tournaments"
                                           class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                    {{ $tournament->name }}
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
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.teams.merge.categories.news') }}</span>
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
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.teams.merge.categories.logos') }}</span>
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

                {{-- Roles --}}
                @if ($roleItems->isNotEmpty())
                    <div class="pt-4 border-t border-border-subtle space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="roles.length === {{ $roleItems->count() }}"
                                   @change="roles = $event.target.checked ? @js($roleItems->map(fn ($item) => $item->role_id.':'.$item->user_id)) : []"
                                   class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">{{ __('admin.teams.merge.categories.roles') }}</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($roleItems as $item)
                                <label class="flex items-center gap-2 text-sm text-gray-300 bg-[#050505] border border-border-subtle rounded-sm px-3 py-2 cursor-pointer">
                                    <input type="checkbox" name="roles[]" value="{{ $item->role_id }}:{{ $item->user_id }}" x-model="roles"
                                           class="rounded-sm border-border-subtle bg-[#050505] text-gc-yellow focus:ring-gc-yellow">
                                    {{ $item->user_name }}
                                    @if ($item->user_username)
                                        <span class="text-gray-500">{{ '@'.$item->user_username }}</span>
                                    @endif
                                    <span class="text-gray-500">({{ $item->role_name }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                </div>

                <div class="pt-6">
                    <x-confirm-modal
                        :title="__('admin.teams.merge.confirm_title')"
                        :body="__('admin.teams.merge.confirm_body', ['source' => $team->name])"
                        :trigger-label="__('admin.teams.merge.submit')"
                        :submit-label="__('admin.teams.merge.submit')"
                        trigger-class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-sm transition active:scale-95 bg-gc-yellow text-black hover:opacity-90"
                        submit-class="bg-gc-yellow text-black hover:opacity-90"
                    >
                        <p class="text-xs text-gray-400">
                            <span x-text="targetName || '{{ __('admin.teams.merge.no_target_selected') }}'"></span>
                            &mdash;
                            <span x-text="total() ? total() + ' {{ __('admin.teams.merge.items_selected') }}' : '{{ __('admin.teams.merge.categories_none') }}'"></span>
                        </p>
                    </x-confirm-modal>
                </div>
            </form>
        </div>
    </div>
@endsection
