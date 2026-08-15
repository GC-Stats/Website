{{--
    GC-Stats — Fail-safe wrapper around forum.embed-card

    Renders embed-card.blade.php through view()->render() inside a
    try/catch instead of a plain <x-forum.embed-card> tag: a bug in one
    embed's rendering (e.g. a mismatched array key in a new match variant)
    must never turn into a 500 for the whole thread/page it's embedded in —
    it should just render as "no longer available" for that one card. Every
    call site that renders App\Models\ForumMessage::parseBody() segments
    should use this instead of the raw component.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@props(['type', 'model', 'variant' => 'header', 'stats' => null, 'filters' => null, 'matchData' => null])

@php
    try {
        $safeHtml = view('components.forum.embed-card', [
            'type' => $type,
            'model' => $model,
            'variant' => $variant,
            'stats' => $stats,
            'filters' => $filters,
            'matchData' => $matchData,
        ])->render();
    } catch (\Throwable $e) {
        report($e);
        $safeHtml = null;
    }
@endphp

@if ($safeHtml !== null)
    {!! $safeHtml !!}
@else
    <span class="text-xs text-gray-600 italic">{{ __('forum.embed.missing') }}</span>
@endif
