{{--
    GC-Stats — Wingman artwork credit modal

    Included once (layouts/app.blade.php). Opened from anywhere on the page
    via the `wingman-modal-open` window event (dispatched by
    wingman-credit.blade.php, which can live in a totally different Alpine
    scope — header logo vs. the "More plants" stats easter egg — hence the
    plain window event instead of a shared x-data). Same centered
    teleported-modal pattern as widget/index.blade.php.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $wingmanArtist = config('wingman_artist');

    $wingmanSocialIcons = [
        'twitter' => 'fab-twitter',
        'x' => 'fab-twitter',
        'instagram' => 'fab-instagram',
        'twitch' => 'fab-twitch',
        'discord' => 'fab-discord',
        'github' => 'fab-github',
        'tiktok' => 'fab-tiktok',
        'youtube' => 'fab-youtube',
    ];
@endphp

<div x-data="{ wingmanModalOpen: false }" @wingman-modal-open.window="wingmanModalOpen = true">
    <template x-teleport="body">
        <div x-show="wingmanModalOpen" x-cloak
             class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @keydown.escape.window="wingmanModalOpen = false">
            <div @click.away="wingmanModalOpen = false" role="dialog" aria-modal="true"
                 aria-label="{{ __('layout.wingman.modal_title') }}"
                 class="w-full max-w-sm bg-bg-card border border-border-subtle rounded-2xl shadow-2xl overflow-hidden">

                <div class="relative bg-gradient-to-b from-[var(--brand-yellow)]/15 to-transparent px-6 pt-8 pb-6 text-center">
                    <button type="button" @click="wingmanModalOpen = false"
                            aria-label="{{ __('layout.wingman.modal_close') }}"
                            class="absolute top-3 right-3 text-gray-500 hover:text-white transition-colors">
                        <x-fas-xmark class="w-4 h-4" aria-hidden="true" />
                    </button>

                    <img src="{{ $wingmanArtist['avatar'] ? asset('storage/'.ltrim($wingmanArtist['avatar'], '/')) : asset('storage/images/wingman.webp') }}"
                         alt="{{ $wingmanArtist['name'] }}"
                         class="w-20 h-20 mx-auto rounded-full object-cover border-2 border-[var(--brand-yellow)]/60 bg-black/30 shadow-lg">

                    <h2 class="mt-4 text-sm font-black uppercase tracking-widest text-white">
                        {{ $wingmanArtist['name'] }}
                    </h2>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--brand-yellow)] mt-1">
                        {{ __('layout.wingman.modal_title') }}
                    </p>
                </div>

                <div class="px-6 pb-6 space-y-5">
                    @if($wingmanArtist['bio'])
                        <p class="text-xs text-gray-400 text-center leading-relaxed">
                            {{ $wingmanArtist['bio'] }}
                        </p>
                    @else
                        <p class="text-xs text-gray-600 text-center italic">
                            {{ __('layout.wingman.modal_bio_pending') }}
                        </p>
                    @endif

                    @if(!empty($wingmanArtist['socials']))
                        <div class="flex items-center justify-center gap-3">
                            @foreach($wingmanArtist['socials'] as $platform => $url)
                                @if($url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                       aria-label="{{ $platform }}"
                                       class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:border-[var(--brand-yellow)]/50 hover:bg-white/10 transition-all">
                                        @svg($wingmanSocialIcons[strtolower($platform)] ?? 'fas-link', 'w-4 h-4', ['aria-hidden' => 'true'])
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
