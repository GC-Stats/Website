{{--
    GC-Stats — Player overview page

    Displays the player's profile overview: bio, current team, recent
    matches and key stats.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('player.title.index', ["player" => $player['handle']]))
@section('description', \Illuminate\Support\Str::limit(strip_tags($player['bio'] ?? ''), 160) ?: __('player.title.index', ["player" => $player['handle']]))
@section('canonical', route('players.show', [$player['id'], str($player['handle'] ?? '')->slug()]))
@section('og_image', $player['profile_photo'] ?? asset('web-app-manifest-512x512.png'))

@push('schema')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $player['handle'],
    'image' => $player['profile_photo'] ?? null,
    'url' => route('players.show', [$player['id'], str($player['handle'] ?? '')->slug()]),
    'memberOf' => isset($currentTeam['name']) ? [
        '@type' => 'SportsTeam',
        'name' => $currentTeam['name'],
    ] : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@section('content')
    @include("player.header")

    <div class="grid grid-cols-12 gap-6">
        <aside class="col-span-12 lg:col-span-3 space-y-2">
            @include('news._sidebar', ['news' => $news, 'sectionTitle' => __('news.press_section')])
        </aside>

        <section class="col-span-12 lg:col-span-6 space-y-4">
            @foreach(array_merge($upcomingMatches, $pastMatches) as $match)
                <x-match :match="$match" />
            @endforeach

            @if(count($upcomingMatches) + count($pastMatches) > 0)
                <a href="{{ route('players.matches', [$player['id'], Str::routeSlug($player['handle'] ?? '', $player['id'])]) }}" class="group flex items-center justify-center gap-2 py-3 bg-white/[0.02] border border-white/5 rounded-sm hover:border-[var(--brand-yellow)]/30 hover:bg-bg-main transition-all duration-300 text-[10px] font-black uppercase tracking-[0.15em] text-white/50 hover:text-gc-yellow">
                    <span>{{ __("player.seemore") }}</span>
                    <x-fas-chevron-right class="w-2.5 h-2.5 inline-block transform group-hover:translate-x-0.5 transition-transform" aria-hidden="true" />
                </a>
            @endif
        </section>

        <aside class="col-span-12 lg:col-span-3 space-y-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __("player.current_team") }}</span>
                    <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                </div>
                @if($currentTeam)
                    <a href="{{ route('teams.show', [$currentTeam['id'], str($currentTeam['name'] ?? '')->slug()]) }}" class="group block mb-2">
                        <div class="tournament-card bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="relative shrink-0">
                                    <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                    <img class="w-10 h-10 object-contain" src="{{ $currentTeam['logo'] ?? asset('storage/images/default-team.webp') }}" alt="">
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black uppercase tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">
                                        {{ $currentTeam['name'] }}
                                    </p>

                                    <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest flex items-center gap-2">
                                        {{ $currentTeam['pivot']['role'] ?? '' }}
                                        <span class="w-1 h-1 bg-white/10 rounded-full"></span>
                                        Since {{ \App\Helpers\PivotDate::format($currentTeam['pivot']['joined_at'], 'm/Y') ?? 'UNKNOWN' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                @else
                    <p class="text-xs font-bold text-gray-500">{{ __("player.no_team") }}</p>
                @endif
            </div>

            @if(count($pastTeams) > 0)
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __("player.old_team") }}</span>
                        <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                        <a href="{{ route('players.history', [$player['id'], Str::routeSlug($player['handle'] ?? '', $player['id'])]) }}" class="group flex items-center gap-1 text-[9px] font-black uppercase tracking-[0.15em] text-white/30 hover:text-gc-yellow transition-colors shrink-0">
                            <span>{{ __("player.seemore") }}</span>
                            <x-fas-chevron-right class="w-2.5 h-2.5 inline-block transform group-hover:translate-x-0.5 transition-transform" aria-hidden="true" />
                        </a>
                    </div>

                    <div class="space-y-2">
                        @foreach($pastTeams as $oldTeam)
                            <a href="{{ route('teams.show', [$oldTeam['id'], str($oldTeam['name'] ?? '')->slug()]) }}" class="group block mb-2">
                                <div class="tournament-card bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                                    <div class="flex items-center gap-4">
                                        <div class="relative shrink-0">
                                            <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                            <img class="w-10 h-10 object-contain" src="{{ $oldTeam['logo'] ?? asset('storage/images/default-team.webp') }}" alt="">
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-black uppercase tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">
                                                {{ $oldTeam['name'] }}
                                            </p>

                                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest flex items-center gap-2">
                                                {{ $oldTeam['pivot']['role'] ?? '' }}
                                                <span class="w-1 h-1 bg-white/10 rounded-full"></span>
                                                {{ \App\Helpers\PivotDate::format($oldTeam['pivot']['joined_at'], 'm/Y') ?? 'UNKNOWN' }}
                                                - {{ isset($oldTeam['pivot']['left_at']) ? (\App\Helpers\PivotDate::format($oldTeam['pivot']['left_at'], 'm/Y') ?? 'Now') : 'Now' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
@endsection
