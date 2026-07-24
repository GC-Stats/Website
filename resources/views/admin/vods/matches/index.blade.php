{{--
    GC-Stats — Admin: matches with a linked VOD (list all)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.vods.matches.title'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ __('admin.vods.matches.title') }}</h1>

        <a href="{{ route('admin.vods.create') }}"
           class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            + {{ __('admin.vods.matches.create_title') }}
        </a>
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.vods.matches.match') }}</th>
                    <x-admin.sortable-th col="tournament" :sort="$sort" :direction="$direction">{{ __('admin.vods.matches.tournament') }}</x-admin.sortable-th>
                    <x-admin.sortable-th col="scheduled_at" :sort="$sort" :direction="$direction">{{ __('admin.matches.scheduled_at') }}</x-admin.sortable-th>
                    <th class="px-4 py-3">{{ __('admin.vods.matches.vods') }}</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($matches as $match)
                    <tr class="border-b border-b-white/10 last:border-b-0 align-top">
                        <td class="px-4 py-3 text-white font-semibold whitespace-nowrap">
                            {{ \App\Support\MatchDisplay::teamShortName($match->teamA, $match->status) }}
                            vs
                            {{ \App\Support\MatchDisplay::teamShortName($match->teamB, $match->status) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-300 whitespace-nowrap">
                            {{ $match->tournament->name ?? '—' }}
                            @if (\App\Support\MatchDisplay::rootPhaseName($match->tournamentPhase))
                                <span class="text-gray-500">— {{ \App\Support\MatchDisplay::rootPhaseName($match->tournamentPhase) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                            @if (\App\Support\MatchDisplay::isUnknownDate($match->scheduled_at))
                                {{ __('admin.matches.unknown_date') }}
                            @else
                                <span data-utc-datetime="{{ $match->scheduled_at->copy()->utc()->toIso8601String() }}">
                                    <span class="js-match-date">{{ $match->scheduled_at->format('Y-m-d') }}</span>
                                    <span class="js-match-time">{{ $match->scheduled_at->format('H:i') }}</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="space-y-1.5 min-w-[280px]">
                                @foreach ($match->vods as $vod)
                                    <div class="flex items-center justify-between gap-3 bg-white/5 border border-white/10 rounded-lg px-3 py-1.5">
                                        <a href="{{ $vod->url }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs text-white hover:text-gc-yellow transition min-w-0">
                                            @if ($vod->icon)
                                                @svg($vod->icon, 'w-3 h-3 flex-shrink-0', ['aria-hidden' => 'true'])
                                            @endif
                                            <span class="fi fi-{{ $vod->language_code === \App\Support\Countries::INTERNATIONAL ? 'un' : $vod->language_code }} flex-shrink-0"></span>
                                            <span class="truncate">{{ $vod->gameMap->map_name ?? __('admin.vods.match.whole_match') }}</span>
                                        </a>

                                        <div class="flex items-center gap-2 flex-shrink-0">
                                        <x-modal :title="__('admin.vods.edit.title')">
                                            <x-slot:trigger>
                                                <button type="button"
                                                        class="font-bold uppercase text-[10px] tracking-widest px-2.5 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                                    {{ __('admin.vods.matches.edit') }}
                                                </button>
                                            </x-slot:trigger>

                                            <form method="POST" action="{{ route('admin.matches.vods.update', [$match->tournament, $match, $vod]) }}" class="space-y-4">
                                                @csrf
                                                @method('PUT')

                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.url') }}</label>
                                                    <input type="url" name="url" required maxlength="2048" value="{{ $vod->url }}"
                                                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                                </div>

                                                <div x-data="{
                                                        open: false,
                                                        query: @js(($countries[$vod->language_code] ?? $vod->language_code).' ('.strtoupper($vod->language_code).')'),
                                                        selected: @js($vod->language_code),
                                                        countries: @js($countries),
                                                        select(code, label) { this.selected = code; this.query = label; this.open = false; },
                                                        flagClass(code) { return code === '{{ \App\Support\Countries::INTERNATIONAL }}' ? 'un' : code; },
                                                     }" class="relative" @click.away="open = false">
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">
                                                        {{ __('admin.vods.fields.language_code') }}
                                                    </label>
                                                    <input type="hidden" name="language_code" :value="selected">
                                                    <input type="text" x-model="query" @focus="open = true" autocomplete="off" required
                                                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                                    <div x-show="open" x-cloak
                                                         class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto bg-[#0a0a0a] border border-white/10 rounded-lg shadow-xl">
                                                        <template x-for="[code, name] in Object.entries(countries)" :key="code">
                                                            <div x-show="query === '' || (name + ' ' + code).toLowerCase().includes(query.toLowerCase())"
                                                                 @click="select(code, name + ' (' + code.toUpperCase() + ')')"
                                                                 class="flex items-center gap-2 px-4 py-2 text-sm text-white cursor-pointer hover:bg-white/5">
                                                                <span class="fi shadow-sm flex-shrink-0" :class="'fi-' + flagClass(code)"></span>
                                                                <span x-text="name + ' (' + code.toUpperCase() + ')'"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.map') }}</label>
                                                    <select name="game_map_id"
                                                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                                        <option value="" @selected(! $vod->game_map_id) style="background-color:#0a0a0a;color:#fff;">{{ __('admin.vods.fields.map_none') }}</option>
                                                        @foreach ($match->game_maps as $map)
                                                            <option value="{{ $map->id }}" @selected($vod->game_map_id === $map->id) style="background-color:#0a0a0a;color:#fff;">{{ $map->map_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                @if (! $vodsRestricted)
                                                    <div>
                                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.publisher') }}</label>
                                                        <select name="publisher_id"
                                                                class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                                                            <option value="" @selected(! $vod->publisher_id)>{{ __('admin.vods.fields.publisher_none') }}</option>
                                                            @foreach ($vodPublishers as $publisherOption)
                                                                <option value="{{ $publisherOption->id }}" @selected($vod->publisher_id === $publisherOption->id)>{{ $publisherOption->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @else
                                                    <input type="hidden" name="publisher_id" value="{{ $vod->publisher_id ?? $vodPublishers->first()?->id }}">
                                                @endif

                                                <button type="submit"
                                                        class="w-full font-bold uppercase text-[10px] tracking-widest px-4 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105">
                                                    {{ __('admin.vods.edit.submit') }}
                                                </button>
                                            </form>
                                        </x-modal>

                                        <form method="POST" action="{{ route('admin.matches.vods.destroy', [$match->tournament, $match, $vod]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-confirm-modal
                                                :title="__('admin.vods.matches.unlink_confirm_title')"
                                                :body="__('admin.vods.matches.unlink_confirm_body')"
                                                :trigger-label="__('admin.vods.matches.unlink')"
                                                :submit-label="__('admin.vods.matches.unlink')"
                                                trigger-class="font-bold uppercase text-[10px] tracking-widest px-2.5 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                                submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                            />
                                        </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('match.show', $match->id) }}" target="_blank" rel="noopener noreferrer"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.vods.matches.public_page') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.vods.matches.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $matches->links() }}
@endsection
