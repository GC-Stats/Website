{{--
    GC-Stats — Wingman artwork credit trigger

    Small reusable wrapper around the wingman.webp artwork: a native title
    tooltip on hover ("Made by X — click for more info"), and a click that
    opens the artist credit modal (public.partials.wingman-modal, included
    once in layouts/app.blade.php) via a window event — this can live in a
    completely different Alpine scope than the modal (header logo vs. the
    "More plants" stats easter egg).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['imgClass' => 'h-6 w-6'])

<img src="{{ asset('storage/images/wingman.webp') }}"
     alt="Wingman"
     title="{{ __('layout.wingman.hover', ['name' => config('wingman_artist.name')]) }}"
     @click.stop.prevent="window.dispatchEvent(new CustomEvent('wingman-modal-open'))"
     class="{{ $imgClass }} object-contain cursor-pointer">
