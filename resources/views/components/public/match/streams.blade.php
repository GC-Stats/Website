{{--
    GC-Stats — Match streams section

    Lists the stream channels linked to a match (see Matchs::streams()),
    styled as its own divider section (same header treatment as the veto
    process block above/below it). $streams is the cached match payload's
    'streams' array (each entry carrying name, platform, url, language_code
    and the appended 'icon' blade-icons key, see App\Models\StreamChannel).
    Every language is shown, distinguished by its flag (fi fi-{code}, same
    system as Player/Team::country_code — see App\Support\Countries).

    Expects $match (full cached match array) and $canLinkStreams — the whole
    section is skipped when there is nothing to show and the current user
    has no right to add one (see App\Http\Controllers\MatchController::
    canLinkStreams()).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php $streams = $match['streams'] ?? []; @endphp

@if (! empty($streams) || ($canLinkStreams ?? false))
    <div class="mt-10 -mx-4 md:-mx-8" @if ($canLinkStreams ?? false) x-data="{ streamFormOpen: false }" @endif>
        <div class="flex items-center justify-center gap-4 mb-8">
            <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent to-white/10"></div>
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[0.5em]">{{ __('match.streams.title') }}</span>
            <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent to-white/10"></div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 px-6">
            @forelse ($streams as $stream)
                <a href="{{ $stream['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="group flex items-center gap-2 px-4 py-2 rounded-full transition active:scale-95 bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:border-white/20">
                    <span class="fi fi-{{ ($stream['language_code'] ?? '') === \App\Support\Countries::INTERNATIONAL ? 'un' : ($stream['language_code'] ?? 'un') }} shadow-sm flex-shrink-0"></span>
                    @svg($stream['icon'], 'w-3.5 h-3.5 flex-shrink-0 text-gray-400 group-hover:text-[var(--brand-yellow)] transition-colors', ['aria-hidden' => 'true'])
                    <span class="text-xs font-bold truncate max-w-[140px]">{{ $stream['name'] }}</span>
                </a>
            @empty
                <p class="text-xs text-gray-500">{{ __('match.streams.empty') }}</p>
            @endforelse
        </div>
    </div>
@endif
