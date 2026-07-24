{{--
    GC-Stats — Admin: match VODs panel

    Included from admin/matches/show.blade.php only, gated by $canLinkVods
    (vods.matches.link or publisher.vods.link — see
    Admin\MatchController::show()). Expects $tournament, $match (with
    'vods.publisher'/'vods.gameMap' eager-loaded), $countries (language
    picker list), $vodPublishers (already restricted to the ones this user
    may pick from) and $vodsRestricted.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div class="lg:col-span-12 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl">
    <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow mb-4">{{ __('admin.vods.match.title') }}</h2>

    <div class="space-y-2 mb-4">
        @forelse ($match->vods as $vod)
            <div class="flex items-center justify-between gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5">
                <a href="{{ $vod->url }}" target="_blank" rel="noopener noreferrer"
                   class="flex items-center gap-2 text-sm text-white hover:text-gc-yellow transition min-w-0">
                    @if ($vod->icon)
                        @svg($vod->icon, 'w-3.5 h-3.5 flex-shrink-0', ['aria-hidden' => 'true'])
                    @endif
                    <span class="fi fi-{{ $vod->language_code === \App\Support\Countries::INTERNATIONAL ? 'un' : $vod->language_code }} flex-shrink-0"></span>
                    <span class="truncate">{{ $vod->gameMap->map_name ?? __('admin.vods.match.whole_match') }}</span>
                    @if ($vod->publisher)
                        <span class="text-[10px] text-gray-500 flex-shrink-0">— {{ $vod->publisher->name }}</span>
                    @endif
                </a>
                <form method="POST" action="{{ route('admin.matches.vods.destroy', [$tournament, $match, $vod]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-red-400 hover:text-red-300 transition flex-shrink-0">
                        {{ __('admin.vods.match.unlink') }}
                    </button>
                </form>
            </div>
        @empty
            <p class="text-xs text-gray-500">{{ __('admin.vods.match.empty') }}</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.matches.vods.store', [$tournament, $match]) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-white/10">
        @csrf

        <div class="sm:col-span-2">
            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.url') }}</label>
            <input type="url" name="url" required maxlength="2048" placeholder="https://…"
                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            @error('url')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div x-data="{
                open: false,
                query: '',
                selected: '',
                countries: @js($countries),
                select(code, label) { this.selected = code; this.query = label; this.open = false; },
                flagClass(code) { return code === '{{ \App\Support\Countries::INTERNATIONAL }}' ? 'un' : code; },
             }" class="relative" @click.away="open = false">
            <label for="vod_language_code_query" class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">
                {{ __('admin.vods.fields.language_code') }}
            </label>
            <input type="hidden" name="language_code" :value="selected">
            <input id="vod_language_code_query" type="text" x-model="query" @focus="open = true" autocomplete="off" required
                   placeholder="{{ __('admin.vods.fields.language_code_search') }}"
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
            @error('language_code')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.map') }}</label>
            <select name="game_map_id"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                <option value="" style="background-color:#0a0a0a;color:#fff;">{{ __('admin.vods.fields.map_none') }}</option>
                @foreach ($match->game_maps as $map)
                    <option value="{{ $map->id }}" style="background-color:#0a0a0a;color:#fff;">{{ $map->map_name }}</option>
                @endforeach
            </select>
        </div>

        @if (! $vodsRestricted)
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">{{ __('admin.vods.fields.publisher') }}</label>
                <select name="publisher_id"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
                    <option value="">{{ __('admin.vods.fields.publisher_none') }}</option>
                    @foreach ($vodPublishers as $publisherOption)
                        <option value="{{ $publisherOption->id }}">{{ $publisherOption->name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="publisher_id" value="{{ $vodPublishers->first()?->id }}">
        @endif

        <div class="sm:col-span-2">
            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105">
                {{ __('admin.vods.match.add') }}
            </button>
        </div>
    </form>
</div>
