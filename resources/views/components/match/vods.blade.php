{{--
    GC-Stats — Match VODs section

    Same treatment as components/match/streams.blade.php (divider header +
    rounded pills), shown instead of the streams section once a match is
    finished (see resources/views/match.blade.php). $vods is the cached
    match payload's 'vods' array (each entry carrying url, language_code,
    the appended nullable 'icon' blade-icons key — see App\Models\Vod::
    platformIcon(), only set for YouTube/Twitch — and 'game_map' when tied
    to one specific map). Every language is shown via its flag (fi
    fi-{code}, same system as Player/Team::country_code — see
    App\Support\Countries).

    Expects $match (full cached match array) and $canLinkVods — the whole
    section is skipped when there is nothing to show and the current user
    has no right to add one (see App\Http\Controllers\MatchController::
    canLinkPermission()).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php $vods = $match['vods'] ?? []; @endphp

@if (! empty($vods) || ($canLinkVods ?? false))
    <div class="mt-10 -mx-4 md:-mx-8" @if ($canLinkVods ?? false) x-data="{ vodFormOpen: false }" @endif>
        <div class="flex items-center justify-center gap-4 mb-8">
            <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent to-white/10"></div>
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[0.5em]">{{ __('match.vods.title') }}</span>
            <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent to-white/10"></div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 px-6">
            @forelse ($vods as $vod)
                <a href="{{ $vod['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="group flex items-center gap-2 px-4 py-2 rounded-full transition active:scale-95 bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:border-white/20">
                    <span class="fi fi-{{ ($vod['language_code'] ?? '') === \App\Support\Countries::INTERNATIONAL ? 'un' : ($vod['language_code'] ?? 'un') }} shadow-sm flex-shrink-0"></span>
                    @if ($vod['icon'] ?? null)
                        @svg($vod['icon'], 'w-3.5 h-3.5 flex-shrink-0 text-gray-400 group-hover:text-[var(--brand-yellow)] transition-colors', ['aria-hidden' => 'true'])
                    @endif
                    <span class="text-xs font-bold truncate max-w-[140px]">{{ $vod['game_map']['map_name'] ?? __('match.vods.whole_match') }}</span>
                </a>
            @empty
                <p class="text-xs text-gray-500">{{ __('match.vods.empty') }}</p>
            @endforelse
        </div>
    </div>
@endif
