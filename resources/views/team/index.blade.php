{{--
    GC-Stats — Team overview page

    Displays the team's profile overview: bio, current roster, recent
    matches and key stats.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('team.title.index', ["team" => $team['name']]))
@section('description', \Illuminate\Support\Str::limit(strip_tags($team['bio'] ?? ''), 160) ?: __('team.title.index', ["team" => $team['name']]))
@section('canonical', route('teams.show', [$team['id'], str($team['name'] ?? '')->slug()]))
@section('og_image', $team['logo'] ?? asset('web-app-manifest-512x512.png'))

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'SportsTeam',
    'name' => $team['name'],
    'logo' => $team['logo'] ?? null,
    'url' => route('teams.show', [$team['id'], str($team['name'] ?? '')->slug()]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endpush

@section('content')
    @include('team.header')

    <div class="grid grid-cols-12 gap-6">
        <aside class="col-span-12 lg:col-span-3 space-y-4">
            <x-achievements-panel :achievements="$achievements ?? []" />

            @include('news._sidebar', ['news' => $news, 'sectionTitle' => __('news.press_section')])

            @if(count($pastPlayers) > 0)
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __("team.old_players") }}</span>
                    <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
                    <a href="{{ route('teams.history', [$team['id'], Str::routeSlug($team['name'] ?? '', $team['id'])]) }}" class="group flex items-center gap-1 text-[9px] font-black uppercase tracking-[0.15em] text-white/30 hover:text-gc-yellow transition-colors shrink-0">
                        <span>{{ __("team.seemore") }}</span>
                        <x-fas-chevron-right class="w-2.5 h-2.5 inline-block transform group-hover:translate-x-0.5 transition-transform" aria-hidden="true" />
                    </a>
                </div>

                <div class="space-y-2">
                    @foreach($pastPlayers as $player)
                        <a href="{{ route('players.show', [$player['id'], str($player['handle'] ?? '')->slug()]) }}" class="group block mb-2">
                            <div class="tournament-card bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                                <div class="flex items-center gap-4">
                                    <div class="relative shrink-0">
                                        @if($player['profile_photo'])
                                            <img src="{{ $player['profile_photo'] }}" alt="{{ $player['handle'] }}"
                                                 class="w-10 h-10 object-cover border border-white/10 rounded-lg bg-black/40">
                                        @else
                                            <div class="w-10 h-10 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10">
                                                <span class="text-md md:text-l font-black text-[var(--brand-yellow)]">
                                                    {{ strtoupper(substr($player['handle'], 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-black uppercase tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">
                                            {{ $player['handle'] }}
                                        </p>

                                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest flex items-center gap-2">
                                            {{ \App\Helpers\RosterRole::label($player['pivot']['role'] ?? null) }}
                                            <span class="w-1 h-1 bg-white/10 rounded-full"></span>
                                            {{ \App\Helpers\PivotDate::format($player['pivot']['joined_at'], 'm/Y') ?? 'UNKNOWN' }}
                                            - {{ isset($player['pivot']['left_at']) ? (\App\Helpers\PivotDate::format($player['pivot']['left_at'], 'm/Y') ?? 'Now') : 'Now' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </aside>

        <section class="col-span-12 lg:col-span-6 space-y-4">
            @foreach($matches as $match)
                <x-match :match="$match" />
            @endforeach

            @if(count($matches) > 0)
                <a href="{{ route('teams.matches', [$team['id'], Str::routeSlug($team['name'] ?? '', $team['id'])]) }}" class="group flex items-center justify-center gap-2 py-3 bg-white/[0.02] border border-white/5 rounded-sm hover:border-[var(--brand-yellow)]/30 hover:bg-bg-main transition-all duration-300 text-[10px] font-black uppercase tracking-[0.15em] text-white/50 hover:text-gc-yellow">
                    <span>{{ __("team.seemore") }}</span>
                    <x-fas-chevron-right class="w-2.5 h-2.5 inline-block transform group-hover:translate-x-0.5 transition-transform" aria-hidden="true" />
                </a>
            @endif
        </section>

        <aside class="col-span-12 lg:col-span-3 space-y-4">
            <div class="flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-white/60 shrink-0">{{ __("team.players") }}</span>
                <div class="h-px flex-grow" style="background: linear-gradient(90deg, rgba(228,174,34,0.5) 0%, rgba(228,174,34,0.05) 60%, transparent 100%)"></div>
            </div>

            <div class="space-y-2">
                @foreach($currentRoster as $player)
                    <a href="{{ route('players.show', [$player['id'], str($player['handle'] ?? '')->slug()]) }}" class="group block mb-2">
                        <div class="tournament-card bg-[#050505] hover:bg-bg-main border border-white/5 rounded-sm p-3 hover:border-[var(--brand-yellow)]/30 transition-all duration-300 shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="relative shrink-0">
                                    @if($player['profile_photo'])
                                        <img src="{{ $player['profile_photo'] }}" alt="{{ $player['handle'] }}"
                                             class="w-10 h-10 object-cover border border-white/10 rounded-lg bg-black/40">
                                    @else
                                        <div class="w-10 h-10 flex items-center justify-center border border-white/10 rounded-lg bg-[var(--brand-yellow)]/10">
                                            <span class="text-md md:text-l font-black text-[var(--brand-yellow)]">
                                                {{ strtoupper(substr($player['handle'], 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black uppercase tracking-tight text-white group-hover:text-[var(--brand-yellow)] transition-colors truncate">
                                        {{ $player['handle'] }}
                                    </p>

                                    <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest flex items-center gap-2">
                                        {{ \App\Helpers\RosterRole::label($player['pivot']['role'] ?? null) }}
                                        <span class="w-1 h-1 bg-white/10 rounded-full"></span>
                                        Since {{ \App\Helpers\PivotDate::format($player['pivot']['joined_at'], 'm/Y') ?? 'UNKNOWN' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </aside>
    </div>
@endsection
