{{--
    GC-Stats — Social link icons

    Renders a "platform => handle" map (see User::socials) as a row of
    clickable icon buttons, using App\Support\SocialLinkConfig for the
    URL prefix + icon per platform.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['socials'])

@php
    $config = \App\Support\SocialLinkConfig::map();
@endphp

@if (! empty($socials))
    <div {{ $attributes->class(['flex gap-2']) }}>
        @foreach ($socials as $platform => $username)
            @if ($username && $config->has($platform))
                @php $cfg = $config->get($platform); @endphp
                <a href="{{ $cfg['url'].$username }}" target="_blank" rel="noopener noreferrer"
                   aria-label="{{ ucfirst($platform) }}: {{ $username }}"
                   class="w-8 h-8 bg-white/5 border border-white/10 rounded-md flex items-center justify-center text-gray-400 hover:text-gc-yellow hover:border-gc-yellow/40 transition-colors">
                    @svg($cfg['icon'], 'w-3 h-3 inline-block', ['aria-hidden' => 'true'])
                </a>
            @endif
        @endforeach
    </div>
@endif
