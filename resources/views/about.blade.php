{{--
    GC-Stats — About Us page

    Public page presenting the project, the team behind GC-Stats, the
    organisation's projects and what's planned for the future. Content is
    stored in the database and managed via the internal API.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('layouts.app')

@section('title', __('about.title'))

@php
    $locale = app()->getLocale();

    $translate = function (?array $value) use ($locale) {
        if (! $value) {
            return null;
        }

        return $value[$locale] ?? $value['en'] ?? reset($value);
    };

    $socialConfig = collect([
        'twitter' => ['url' => 'https://x.com/', 'icon' => 'fab-x-twitter'],
        'twitch' => ['url' => 'https://twitch.tv/', 'icon' => 'fab-twitch'],
        'tiktok' => ['url' => 'https://tiktok.com/@', 'icon' => 'fab-tiktok'],
        'instagram' => ['url' => 'https://instagram.com/', 'icon' => 'fab-instagram'],
        'youtube' => ['url' => 'https://youtube.com/@', 'icon' => 'fab-youtube'],
        'discord' => ['url' => '#', 'icon' => 'fab-discord'],
        'email' => ['url' => 'mailto:', 'icon' => 'fas-envelope'],
    ]);

    $projectTypeConfig = collect([
        'website' => ['icon' => 'fas-globe', 'color' => '#e4ae22'],
        'api' => ['icon' => 'fas-code', 'color' => '#F54927'],
        'dashboard' => ['icon' => 'fab-discord', 'color' => '#22c55e'],
        'discordbot' => ['icon' => 'fab-discord', 'color' => '#5865F2'],
    ]);
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-8 lg:col-start-3 space-y-12">

            <div class="border-b border-border-subtle pb-6 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                    {{ __('about.title') }}
                </h1>
            </div>

            @if($sections->isNotEmpty())
                @foreach($sections as $section)
                    <div class="space-y-3">
                        <div class="border-b border-border-subtle pb-2">
                            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                                {{ $translate($section->title) }}
                            </h2>
                        </div>
                        <div class="text-sm text-gray-300 leading-relaxed">
                            {!! nl2br(e($translate($section->content))) !!}
                        </div>
                    </div>
                @endforeach
            @endif

            @if($team->isNotEmpty())
                <div class="space-y-4">
                    <div class="border-b border-border-subtle pb-2">
                        <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                            {{ __('about.team.title') }}
                        </h2>
                    </div>
                    <div class="flex flex-wrap justify-center gap-4">
                        @foreach($team as $member)
                            <div class="group relative bg-white/[0.03] border border-white/10 rounded-2xl p-6 shadow-xl hover:bg-white/[0.05] hover:border-[var(--brand-yellow)]/30 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center overflow-hidden w-full sm:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.667rem)]">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/[0.02] -rotate-45 translate-x-12 -translate-y-12 pointer-events-none"></div>

                                <div class="relative shrink-0 mb-4">
                                    <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                    <img src="{{ $member->photo_url ?? asset('storage/images/default-player.webp') }}"
                                         alt="{{ $member->name }}"
                                         class="relative w-20 h-20 rounded-full object-cover border-2 border-white/10 bg-black/60 shadow-lg group-hover:border-[var(--brand-yellow)]/40 group-hover:scale-105 transition-all duration-300">
                                </div>

                                <h3 class="relative text-sm font-black text-white uppercase tracking-wide">
                                    {{ $member->name }}
                                </h3>

                                @php $role = $translate($member->role); @endphp
                                @if($role)
                                    <p class="relative text-[10px] font-bold uppercase tracking-widest text-gc-yellow mt-1">
                                        {{ $role }}
                                    </p>
                                @endif

                                @php $bio = $translate($member->bio); @endphp
                                @if($bio)
                                    <p class="relative text-xs text-gray-400 leading-relaxed mt-3">
                                        {!! nl2br(e($bio)) !!}
                                    </p>
                                @endif

                                @if(! empty($member->socials))
                                    <div class="relative flex gap-2 mt-4">
                                        @foreach($member->socials as $platform => $username)
                                            @if($username && $socialConfig->has($platform))
                                                @php $cfg = $socialConfig->get($platform); @endphp
                                                <a href="{{ $cfg['url'] . $username }}" target="_blank" rel="noopener noreferrer"
                                                   aria-label="{{ ucfirst($platform) }}: {{ $username }}"
                                                   class="w-8 h-8 bg-white/5 border border-white/10 rounded-md flex items-center justify-center text-gray-400 hover:text-gc-yellow hover:border-gc-yellow/40 transition-colors">
                                                    @svg($cfg['icon'], 'w-3 h-3 inline-block', ['aria-hidden' => 'true'])
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($projects->isNotEmpty())
                <div class="space-y-4">
                    <div class="border-b border-border-subtle pb-2">
                        <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/90">
                            {{ __('about.projects.title') }}
                        </h2>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($projects as $project)
                            @php
                                $typeKey = $project->type ? strtolower($project->type) : null;
                                $typeCfg = $typeKey ? $projectTypeConfig->get($typeKey) : null;
                                $typeColor = $typeCfg['color'] ?? '#e4ae22';
                            @endphp
                            <div class="group relative bg-[#050505] hover:bg-bg-main border border-[#0a0a0a]/30 hover:border-[color:var(--card-color)] rounded-sm p-5 transition-all duration-300 shadow-lg flex flex-col items-center text-center overflow-hidden"
                                 style="--card-color: {{ $typeColor }}">
                                <div class="absolute inset-x-0 top-0 h-[2px]" style="background: {{ $typeColor }}"></div>

                                @if($project->logo_url)
                                    <div class="relative shrink-0 w-14 h-14 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                        <div class="absolute inset-0 bg-[var(--brand-yellow)] opacity-0 group-hover:opacity-10 blur-md transition-opacity"></div>
                                        <img src="{{ $project->logo_url }}" alt="{{ $project->name }}"
                                             class="relative max-w-full max-h-full object-contain">
                                    </div>
                                @endif

                                <h3 class="text-sm font-black text-white uppercase tracking-wide">
                                    {{ $project->name }}
                                </h3>

                                @if($project->type)
                                    <span class="inline-flex items-center gap-1.5 mt-2 px-2 py-1 rounded-sm text-[9px] font-black uppercase tracking-widest"
                                          style="background: {{ $typeColor }}1a; color: {{ $typeColor }}">
                                        @if($typeCfg)
                                            @svg($typeCfg['icon'], 'w-2.5 h-2.5 inline-block', ['aria-hidden' => 'true'])
                                        @endif
                                        {{ $project->type }}
                                    </span>
                                @endif

                                @php $description = $translate($project->description); @endphp
                                @if($description)
                                    <p class="text-xs text-gray-400 leading-relaxed mt-3">
                                        {!! nl2br(e($description)) !!}
                                    </p>
                                @endif

                                @if($project->url)
                                    <a href="{{ $project->url }}" target="_blank" rel="noopener noreferrer"
                                       class="absolute inset-0" aria-label="{{ __('about.projects.visit') }} {{ $project->name }}">
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
